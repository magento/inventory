<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\CatalogInventory\Model\Quote\Item\QuantityValidator;

use Magento\CatalogInventory\Helper\Data;
use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;
use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\Option;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Event\Observer;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

class AdaptQuantityValidatorPlugin
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var Option
     */
    private $optionInitializer;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @param StockResolverInterface $stockResolver
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param IsProductSalableInterface $isProductSalable
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param Option $optionInitializer
     * @param ObjectFactory $objectFactory
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        IsProductSalableInterface $isProductSalable,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        Option $optionInitializer,
        ObjectFactory $objectFactory
    ) {
        $this->stockResolver = $stockResolver;
        $this->optionInitializer = $optionInitializer;
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->isProductSalable = $isProductSalable;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->objectFactory = $objectFactory;
    }

    /**
     * @param QuantityValidator $subject
     * @param callable $proceed
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundValidate(QuantityValidator $subject, callable $proceed, Observer $observer): void
    {
        $quoteItem = $observer->getEvent()->getItem();
        if (!$quoteItem ||
            !$quoteItem->getProductId() ||
            !$quoteItem->getQuote()
        ) {
            return;
        }

        $product = $quoteItem->getProduct();
        $sku = $this->getSkusByProductIds->execute([$product->getId()])[$product->getId()];
        $qty = $quoteItem->getQty();
        $stock = $this->stockResolver->get(
            SalesChannelInterface::TYPE_WEBSITE,
            $product->getStore()->getWebsite()->getCode()
        );

        if ($quoteItem->getQuote()->getIsSuperMode()) {
            return;
        }

        $parentStockStatus = 1;
        if ($quoteItem->getParentItem()) {
            $parentProduct = $quoteItem->getParentItem()->getProduct();
            $parentSku = $this->getSkusByProductIds->execute([$parentProduct->getId()])[$parentProduct->getId()];
            $parentStockStatus = (int)$this->isProductSalable->execute($parentSku, (int)$stock->getStockId());
        }
        $stockStatus = (int)$this->isProductSalable->execute($sku, (int)$stock->getStockId());

        if ($stockStatus === Stock::STOCK_OUT_OF_STOCK || $parentStockStatus === Stock::STOCK_OUT_OF_STOCK) {
            $quoteItem->addErrorInfo(
                'cataloginventory',
                Data::ERROR_QTY,
                __('This product is out of stock.')
            );
            $quoteItem->getQuote()->addErrorInfo(
                'stock',
                'cataloginventory',
                Data::ERROR_QTY,
                __('Some of the products are out of stock.')
            );
            return;
        } else {
            $this->removeErrorsFromQuoteAndItem($quoteItem, Data::ERROR_QTY);
        }

        if (($options = $quoteItem->getQtyOptions()) && $qty > 0) {
            foreach ($options as $option) {
                $this->optionInitializer->initialize($option, $quoteItem, $qty);
            }
            $removeError = true;
            foreach ($options as $option) {
                $result = $option->getStockStateResult();
                if ($result->getHasError()) {
                    $option->setHasError(true);
                    $removeError = false;
                    $this->addErrorInfoToQuote($result, $quoteItem, $removeError);
                }
            }
            if ($removeError) {
                $this->removeErrorsFromQuoteAndItem($quoteItem, Data::ERROR_QTY);
            }
        } else {
            $qty = $quoteItem->getParentItem() ? $quoteItem->getParentItem()->getQty() * $qty : $qty;
            $isSalableResult = $this->isProductSalableForRequestedQty->execute(
                $sku,
                (int)$stock->getStockId(),
                $qty
            );

            $result = $this->objectFactory->create();
            $result->setHasError(false);
            foreach ($isSalableResult->getErrors() as $error) {
                $result->setHasError(true)->setMessage($error->getMessage())->setQuoteMessage($error->getMessage())
                    ->setQuoteMessageIndex('qty');
            }

            if ($result->getHasError()) {
                $this->addErrorInfoToQuote($result, $quoteItem);
            } else {
                $this->removeErrorsFromQuoteAndItem($quoteItem, Data::ERROR_QTY);
            }
        }
    }

    /**
     * Removes error statuses from quote and item, set by this observer
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param int $code
     * @return void
     */
    protected function removeErrorsFromQuoteAndItem($item, int $code) :void
    {
        if ($item->getHasError()) {
            $params = ['origin' => 'cataloginventory', 'code' => $code];
            $item->removeErrorInfosByParams($params);
        }

        $quote = $item->getQuote();
        if ($quote->getHasError()) {
            $quoteItems = $quote->getItemsCollection();
            $canRemoveErrorFromQuote = true;
            foreach ($quoteItems as $quoteItem) {
                if ($quoteItem->getItemId() == $item->getItemId()) {
                    continue;
                }

                $errorInfos = $quoteItem->getErrorInfos();
                foreach ($errorInfos as $errorInfo) {
                    if ($errorInfo['code'] == $code) {
                        $canRemoveErrorFromQuote = false;
                        break;
                    }
                }

                if (!$canRemoveErrorFromQuote) {
                    break;
                }
            }

            if ($canRemoveErrorFromQuote) {
                $params = ['origin' => 'cataloginventory', 'code' => $code];
                $quote->removeErrorInfosByParams(null, $params);
            }
        }
    }

    /**
     * Add error information to Quote Item
     *
     * @param DataObject $result
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param bool $removeError
     * @return void
     */
    private function addErrorInfoToQuote(DataObject $result, $quoteItem)
    {
        $quoteItem->addErrorInfo(
            'cataloginventory',
            Data::ERROR_QTY,
            $result->getMessage()
        );

        $quoteItem->getQuote()->addErrorInfo(
            $result->getQuoteMessageIndex(),
            'cataloginventory',
            Data::ERROR_QTY,
            $result->getQuoteMessage()
        );
    }
}

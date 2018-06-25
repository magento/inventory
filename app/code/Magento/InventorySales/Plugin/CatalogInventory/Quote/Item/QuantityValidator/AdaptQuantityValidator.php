<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator;

use Magento\CatalogInventory\Helper\Data;
use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;
use Magento\Framework\Event\Observer;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator\AdaptQuantityValidator\ErrorProcessor;
use Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator\AdaptQuantityValidator\ItemValidator;
use Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator\AdaptQuantityValidator\OptionsValidator;
use Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator\AdaptQuantityValidator\StatusValidator;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Multi-stock quote item quantity validator.
 */
class AdaptQuantityValidator
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var StatusValidator
     */
    private $statusValidator;

    /**
     * @var ItemValidator
     */
    private $itemValidator;

    /**
     * @var OptionsValidator
     */
    private $optionsValidator;

    /**
     * @var ErrorProcessor
     */
    private $errorProcessor;

    /**
     * @param StockResolverInterface $stockResolver
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StatusValidator $statusValidator
     * @param ItemValidator $itemValidator
     * @param OptionsValidator $optionsValidator
     * @param ErrorProcessor $errorProcessor
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StatusValidator $statusValidator,
        ItemValidator $itemValidator,
        OptionsValidator $optionsValidator,
        ErrorProcessor $errorProcessor
    ) {
        $this->stockResolver = $stockResolver;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->statusValidator = $statusValidator;
        $this->itemValidator = $itemValidator;
        $this->optionsValidator = $optionsValidator;
        $this->errorProcessor = $errorProcessor;
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
            !$quoteItem->getQuote() ||
            $quoteItem->getQuote()->getIsSuperMode()
        ) {
            return;
        }

        $product = $quoteItem->getProduct();
        $sku = $this->getSkusByProductIds->execute([$product->getId()])[$product->getId()];
        $stock = $this->stockResolver->get(
            SalesChannelInterface::TYPE_WEBSITE,
            $product->getStore()->getWebsite()->getCode()
        );

        if ($this->statusValidator->execute($quoteItem, (int)$stock->getStockId(), $sku)) {
            $this->errorProcessor->removeErrorsFromQuoteAndItem($quoteItem, Data::ERROR_QTY);
            $options = $quoteItem->getQtyOptions();
            if ($options && $quoteItem->getQty() > 0) {
                $this->optionsValidator->execute($quoteItem, $quoteItem->getQty());
            } else {
                $this->itemValidator->execute($quoteItem, $quoteItem->getQty(), $sku, (int)$stock->getStockId());
            }
        }
    }
}

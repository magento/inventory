<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\Stock;

use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\IsProductSalableCondition\BackOrderNotifyCustomerCondition;
use Magento\InventorySales\Model\GetBackOrderQty;
use Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\ProductSalabilityError;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Psr\Log\LoggerInterface;

/**
 * StockStateProvider Class
 *
 * Replacing Legacy StockStateProvider
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockStateProvider
{
    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var FormatInterface
     */
    private $format;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var BackOrderNotifyCustomerCondition
     */
    private $backOrderNotifyCustomerCondition;

    /**
     * @var GetBackOrderQty
     */
    private $getBackOrderQty;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ObjectFactory $objectFactory
     * @param FormatInterface $format
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param BackOrderNotifyCustomerCondition $backOrderNotifyCustomerCondition
     * @param GetBackOrderQty $getBackOrderQty
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param LoggerInterface $logger
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        ObjectFactory $objectFactory,
        FormatInterface $format,
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        BackOrderNotifyCustomerCondition $backOrderNotifyCustomerCondition,
        GetBackOrderQty $getBackOrderQty,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        LoggerInterface $logger
    ) {
        $this->objectFactory = $objectFactory;
        $this->format = $format;
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->backOrderNotifyCustomerCondition = $backOrderNotifyCustomerCondition;
        $this->getBackOrderQty = $getBackOrderQty;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->logger = $logger;
    }

    /**
     * StockStateProvider::execute
     *
     * Replace legacy quote item check
     *
     * @param int $productId
     * @param float $itemQty
     * @param float $qtyToCheck
     * @param float $origQty
     * @param int|null $scopeId
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkQuoteItemQty(
        $productId,
        $itemQty,
        $qtyToCheck,
        $origQty,
        $scopeId
    ) {
        $result = $this->objectFactory->create();
        $result->setHasError(false);

        $qty = max($this->getNumber($itemQty), $this->getNumber($qtyToCheck));

        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];

        $websiteCode = $this->storeManager->getWebsite($scopeId)->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = $stock->getStockId();

        $isSalableResult = $this->isProductSalableForRequestedQty->execute($productSku, (int)$stockId, $qty);

        if ($isSalableResult->isSalable() === false) {
            /** @var ProductSalabilityError $error */
            foreach ($isSalableResult->getErrors() as $error) {
                $result->setHasError(true)->setMessage($error->getMessage())->setQuoteMessage($error->getMessage())
                       ->setQuoteMessageIndex('qty');
            }
        } else {
            $productSalableResult = $this->backOrderNotifyCustomerCondition->execute($productSku, (int)$stockId, $qty);
            if ($productSalableResult->getErrors()) {
                /** @var ProductSalabilityError $error */
                foreach ($productSalableResult->getErrors() as $error) {
                    $result->setMessage($error->getMessage());
                }
            }

            //Ensure ItemBackorders is set. Fixes issue MSI #2218
            $backOrderQty = $this->getBackOrderQty->execute($productSku, (int)$stockId, $qty);
            if ($backOrderQty > 0) {
                $result->setItemBackorders($backOrderQty);
            }
        }

        return $result;
    }

    /**
     * Convert quantity to a valid float
     *
     * @param string|float|int|null $qty
     * @return float|null
     */
    private function getNumber($qty)
    {
        if (!is_numeric($qty)) {
            return $this->format->getNumber($qty);
        }

        return $qty;
    }
}

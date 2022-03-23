<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\IsProductSalableCondition\BackOrderNotifyCustomerCondition;
use Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\ProductSalabilityError;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Backorder class
 *
 * Returns the backorder qty based on product id and stock id
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetBackorder
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
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $isProductSalableForRequestedQtyRequestInterfaceFactory;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var BackOrderNotifyCustomerCondition
     */
    private $backOrderNotifyCustomerCondition;

    /**
     * @var GetBackorderQty
     */
    private $getBackorderQty;

    /**
     * @param ObjectFactory $objectFactory
     * @param FormatInterface $format
     * @param AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty
     * @param IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param BackOrderNotifyCustomerCondition $backOrderNotifyCustomerCondition
     * @param GetBackorderQty|null $getBackorderQty
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        ObjectFactory $objectFactory,
        FormatInterface $format,
        AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty,
        IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager,
        BackOrderNotifyCustomerCondition $backOrderNotifyCustomerCondition,
        GetBackorderQty $getBackorderQty = null
    ) {
        $this->objectFactory = $objectFactory;
        $this->format = $format;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->isProductSalableForRequestedQtyRequestInterfaceFactory = $isProductSalableForRequestedQtyRequestFactory;
        $this->areProductsSalableForRequestedQty = $areProductsSalableForRequestedQty;
        $this->backOrderNotifyCustomerCondition = $backOrderNotifyCustomerCondition;
        $this->getBackorderQty = $getBackorderQty
            ?? ObjectManager::getInstance()->get(GetBackorderQty::class);
    }

    /**
     * Main execute function
     *
     * @param int $productId
     * @param int|float $itemQty
     * @param int|float $qtyToCheck
     * @param int|null $scopeId
     *
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(int $productId, $itemQty, $qtyToCheck, $scopeId): object
    {
        $result = $this->objectFactory->create();
        $result->setHasError(false);

        $qty = max($this->getNumber($itemQty), $this->getNumber($qtyToCheck));

        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];

        $websiteCode = $this->storeManager->getWebsite($scopeId)->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = $stock->getStockId();

        $request = $this->isProductSalableForRequestedQtyRequestInterfaceFactory->create(
            [
                'sku' => $productSku,
                'qty' => $qty,
            ]
        );
        $productsSalableResult = $this->areProductsSalableForRequestedQty->execute([$request], (int)$stockId);
        $productsSalableResult = current($productsSalableResult);

        if ($productsSalableResult->isSalable() === false) {
            /** @var ProductSalabilityError $error */
            foreach ($productsSalableResult->getErrors() as $error) {
                $result->setHasError(true)->setMessage($error->getMessage())->setQuoteMessage($error->getMessage())
                    ->setQuoteMessageIndex('qty')->setErrorCode($error->getCode());
            }
        } else {
            $productSalableResult = $this->backOrderNotifyCustomerCondition->execute($productSku, (int)$stockId, $qty);
            if ($productSalableResult->getErrors()) {
                /** @var ProductSalabilityError $error */
                foreach ($productSalableResult->getErrors() as $error) {
                    $result->setMessage($error->getMessage());
                }
            }
            $backorderQty = $this->getBackorderQty->execute($productSku, (int)$stockId, $qty);
            if ($backorderQty > 0) {
                $result->setItemBackorders($backorderQty);
            }
        }

        return $result;
    }

    /**
     * Convert quantity to a valid float
     *
     * @param int|float $qty
     *
     * @return float
     */
    private function getNumber($qty): float
    {
        if (!is_numeric($qty)) {
            return $this->format->getNumber($qty);
        }

        return $qty;
    }
}

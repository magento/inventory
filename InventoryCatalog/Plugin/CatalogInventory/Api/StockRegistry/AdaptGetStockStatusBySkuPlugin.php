<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetProductStatusForStock;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt getStockStatusBySku for multi stocks.
 */
class AdaptGetStockStatusBySkuPlugin
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetProductStatusForStock
     */
    private $getProductStatusForStock;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param GetProductStatusForStock $getProductStatusForStock
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable,
        GetProductSalableQtyInterface $getProductSalableQty,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        GetProductStatusForStock $getProductStatusForStock
    ) {
        $this->areProductsSalable = $areProductsSalable;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->getProductStatusForStock = $getProductStatusForStock;
    }

    /**
     * Get product stock status by sku considering multi stock environment.
     *
     * @param StockRegistryInterface $subject
     * @param StockStatusInterface $stockStatus
     * @param string $productSku
     * @param int $scopeId
     * @return StockStatusInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetStockStatusBySku(
        StockRegistryInterface $subject,
        StockStatusInterface $stockStatus,
        $productSku,
        $scopeId = null
    ): StockStatusInterface {
        $websiteCode = null === $scopeId
            ? $this->storeManager->getWebsite()->getCode()
            : $this->storeManager->getWebsite($scopeId)->getCode();
        $stockId = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)->getStockId();

        $result = $this->areProductsSalable->execute([$productSku], $stockId);
        $result = current($result);

        $isProductEnabled = $this->getProductStatusForStock->execute($productSku, $stockId);
        $status = $isProductEnabled === Status::STATUS_ENABLED
            ? (int)$result->isSalable()
            : 0;
        try {
            $qty = $this->getProductSalableQty->execute($productSku, $stockId);
        } catch (InputException $e) {
            $qty = 0;
        }

        $stockStatus->setStockStatus($status);
        $stockStatus->setQty($qty);
        return $stockStatus;
    }
}

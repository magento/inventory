<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\GetProductQuantityInStockInterface;
use Magento\InventoryCatalog\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\GetProductQuantityInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * This resource model is responsible for retrieving Product Quantity from Legacy Catalog Stock Item
 * by Stock Resolver. This model provides backward compatibility to Legacy Catalog Stock Item
 */
class GetProductQuantity implements GetProductQuantityInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetProductQuantityInStockInterface
     */
    private $getProductQuantityInStock;

    /**
     * @param StoreManagerInterface $storeManager
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StockResolverInterface $stockResolver
     * @param GetProductQuantityInStockInterface $getProductQuantityInStock
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StockResolverInterface $stockResolver,
        GetProductQuantityInStockInterface $getProductQuantityInStock
    ) {
        $this->storeManager = $storeManager;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->stockResolver = $stockResolver;
        $this->getProductQuantityInStock = $getProductQuantityInStock;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(StockItemInterface $legacyStockItem)
    {
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();

            $productIds = $this->getSkusByProductIds->execute([$legacyStockItem->getProductId()]);
            $productSku = $productIds[$legacyStockItem->getProductId()];

            $stock = $this->stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);

            return $this->getProductQuantityInStock->execute($productSku, (int)$stock->getStockId());
        } catch (NoSuchEntityException $e) {
            return $legacyStockItem->getQty();
        } catch (LocalizedException $e) {
            return $legacyStockItem->getQty();
        }
    }
}

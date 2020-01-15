<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class retrieves product stock data from `cataloginventory_stock_item`
 */
class GetLegacyStockItem
{
    /**
     * @var StockItemInterfaceFactory
     */
    private $stockItemFactory;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $legacyStockItemCriteriaFactory;

    /**
     * @var StockItemRepositoryInterface
     */
    private $legacyStockItemRepository;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var StockItemInterface[]
     */
    private $processedSkuData;

    /**
     * GetLegacyStockItem constructor.
     *
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        StockItemInterfaceFactory $stockItemFactory,
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->stockItemFactory = $stockItemFactory;
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Retrieves product stock data
     *
     * @param string $sku
     * @return StockItemInterface
     */
    public function execute(string $sku): StockItemInterface
    {
        if ($this->processedSkuData !== null && isset($this->processedSkuData[$sku])) {
            return $this->processedSkuData[$sku];
        }

        $searchCriteria = $this->legacyStockItemCriteriaFactory->create();

        try {
            $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];
        } catch (NoSuchEntityException $skuNotFoundInCatalog) {
            $stockItem = $this->stockItemFactory->create();
            // Make possible to Manage Stock for Products removed from Catalog
            $stockItem->setManageStock(true);

            $this->processedSkuData[$sku] = $stockItem;

            return $this->processedSkuData[$sku];
        }

        $searchCriteria->addFilter(StockItemInterface::PRODUCT_ID, StockItemInterface::PRODUCT_ID, $productId);

        // Stock::DEFAULT_STOCK_ID is used until we have proper multi-stock item configuration
        $searchCriteria->addFilter(StockItemInterface::STOCK_ID, StockItemInterface::STOCK_ID, Stock::DEFAULT_STOCK_ID);
        $searchCriteria->setLimit(1, 1);

        $stockItemCollection = $this->legacyStockItemRepository->getList($searchCriteria);
        if ($stockItemCollection->getTotalCount() === 0) {
            $this->processedSkuData[$sku] = $this->stockItemFactory->create();
            return $this->processedSkuData[$sku];
        }

        $stockItems = $stockItemCollection->getItems();
        $stockItem = reset($stockItems);

        $this->processedSkuData[$sku] = $stockItem;

        return $this->processedSkuData[$sku];
    }
}

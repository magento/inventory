<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * @inheritdoc
 */
class GetStockItemData implements GetStockItemDataInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var StockItemDataHandler
     */
    private $stockItemDataHandler;

    /**
     * @param ResourceConnection $resource
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param IsSingleSourceModeInterface|null $isSingleSourceMode
     * @param IsSourceItemManagementAllowedForSkuInterface|null $isSourceItemManagementAllowedForSku
     * @param StockItemDataHandler|null $stockItemDataHandler
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ResourceConnection $resource,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ?IsSingleSourceModeInterface $isSingleSourceMode = null,
        ?IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku = null,
        ?StockItemDataHandler $stockItemDataHandler = null
    ) {
        $this->resource = $resource;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->stockItemDataHandler = $stockItemDataHandler
            ?: ObjectManager::getInstance()->get(StockItemDataHandler::class);
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): ?array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select();

        if ($this->defaultStockProvider->getId() === $stockId) {
            $productId = current($this->getProductIdsBySkus->execute([$sku]));
            $select->from(
                $this->resource->getTableName('cataloginventory_stock_status'),
                [
                    GetStockItemDataInterface::QUANTITY => 'qty',
                    GetStockItemDataInterface::IS_SALABLE => 'stock_status',
                ]
            )->where(
                'product_id = ?',
                $productId
            );
        } else {
            $select->from(
                $this->stockIndexTableNameResolver->execute($stockId),
                [
                    GetStockItemDataInterface::QUANTITY => IndexStructure::QUANTITY,
                    GetStockItemDataInterface::IS_SALABLE => IndexStructure::IS_SALABLE,
                ]
            )->where(
                IndexStructure::SKU . ' = ?',
                $sku
            );
        }

        try {
            $stockItemRow = $connection->fetchRow($select) ?: null;
            /**
             * Fallback to the legacy cataloginventory_stock_item table.
             * Caused by data absence in legacy cataloginventory_stock_status table
             * for disabled products assigned to the default stock.
             */
            if ($stockItemRow === null) {
                $stockItemRow = $this->stockItemDataHandler->getStockItemDataFromStockItemTable($sku, $stockId);
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not receive Stock Item data'), $e);
        }

        return $stockItemRow;
    }
}

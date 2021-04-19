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
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var IsSourceItemManagementAllowedForSkuInterface
     */
    private $isSourceItemManagementAllowedForSku;

    /**
     * @param ResourceConnection $resource
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param IsSingleSourceModeInterface|null $isSingleSourceMode
     * @param IsSourceItemManagementAllowedForSkuInterface|null $isSourceItemManagementAllowedForSku
     */
    public function __construct(
        ResourceConnection $resource,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ?IsSingleSourceModeInterface $isSingleSourceMode = null,
        ?IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku = null
    ) {
        $this->resource = $resource;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->isSingleSourceMode = $isSingleSourceMode
            ?: ObjectManager::getInstance()->get(IsSingleSourceModeInterface::class);
        $this->isSourceItemManagementAllowedForSku = $isSourceItemManagementAllowedForSku
            ?: ObjectManager::getInstance()->get(IsSourceItemManagementAllowedForSkuInterface::class);

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
                $stockItemRow = $this->getStockItemDataFromStockItemTable($sku, $stockId);
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not receive Stock Item data'), $e);
        }

        return $stockItemRow;
    }

    /**
     * Retrieve stock item data for product assigned to the default stock.
     *
     * @param string $sku
     * @param int $stockId
     * @return array|null
     */
    private function getStockItemDataFromStockItemTable(string $sku, int $stockId): ?array
    {
        if ($this->defaultStockProvider->getId() !== $stockId
            || $this->isSingleSourceMode->execute()
            || !$this->isSourceItemManagementAllowedForSku->execute($sku)
        ) {
            return null;
        }

        $productId = current($this->getProductIdsBySkus->execute([$sku]));
        $connection = $this->resource->getConnection();
        $select = $connection->select();

        $select->from(
            $this->resource->getTableName('cataloginventory_stock_item'),
            [
                GetStockItemDataInterface::QUANTITY => 'qty',
                GetStockItemDataInterface::IS_SALABLE => 'is_in_stock',
            ]
        )->where(
            'product_id = ?',
            $productId
        );

        return $connection->fetchRow($select) ?: null;
    }
}

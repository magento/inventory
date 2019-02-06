<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model;

use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Get salable quantity data by sku
 */
class GetSalableQuantityDataBySku
{
    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var GetAssignedStockIdsBySku
     */
    private $getAssignedStockIdsBySku;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param StockRepositoryInterface $stockRepository
     * @param GetAssignedStockIdsBySku $getAssignedStockIdsBySku
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        GetProductSalableQtyInterface $getProductSalableQty,
        StockRepositoryInterface $stockRepository,
        GetAssignedStockIdsBySku $getAssignedStockIdsBySku,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        ResourceConnection $resourceConnection
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockRepository = $stockRepository;
        $this->getAssignedStockIdsBySku = $getAssignedStockIdsBySku;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $sku
     * @return array
     */
    public function execute(array $skus): array
    {
        $stockInfo = [];

        foreach ($this->getStockDataBySkus($skus) as $stockData) {
            $stockId = (int)$stockData['stock_id'];
            $sku = $stockData['sku'];
            $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
            $isManageStock = $stockItemConfiguration->isManageStock();
            $stockInfo[$sku][] = [
                'stock_name' => $stockData['name'],
                'qty' => $isManageStock ? $this->getProductSalableQty->execute($sku, $stockId) : null,
                'manage_stock' => $isManageStock,
            ];
        }

        return $stockInfo;
    }

    private function getStockDataBySkus(array $skus): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from(['main' => $this->resourceConnection->getTableName('inventory_source_item')], ['main.sku']);
        $select->join(
            ['issl' => $this->resourceConnection->getTableName('inventory_source_stock_link')],
            'main.source_code = issl.source_code',
            []
        );
        $select->join(
            ['inv_s' => $this->resourceConnection->getTableName('inventory_stock')],
            'inv_s.stock_id = issl.stock_id',
            ['inv_s.stock_id', 'inv_s.name']
        );
        $select->where('main.sku IN (?)', $skus);
        $stockData = $connection->fetchAll($select);

        return $stockData;
    }
}

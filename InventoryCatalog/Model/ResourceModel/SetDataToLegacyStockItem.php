<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Set data to legacy cataloginventory_stock_item table via plain MySql query
 */
class SetDataToLegacyStockItem
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Updates the legacy stock item table with the given SKU, quantity, and stock status using a MySQL query.
     *
     * @param string $sku
     * @param float $quantity
     * @param int $status
     * @return void
     */
    public function execute(string $sku, float $quantity, int $status)
    {
        $productIds = $this->getProductIdsBySkus->execute([$sku]);

        if (isset($productIds[$sku])) {
            $productId = $productIds[$sku];

            $connection = $this->resourceConnection->getConnection();
            $connection->update(
                $this->resourceConnection->getTableName('cataloginventory_stock_item'),
                [
                    StockItemInterface::QTY => $quantity,
                    StockItemInterface::IS_IN_STOCK => $status,
                ],
                [
                    StockItemInterface::PRODUCT_ID . ' = ?' => $productId,
                    'website_id = ?' => 0,
                ]
            );
        }
    }
}

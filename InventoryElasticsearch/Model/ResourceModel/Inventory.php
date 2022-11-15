<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Class Inventory for stock processing and calculation
 */
class Inventory
{
    /**
     * @var array
     */
    private $stockStatus;

    /**
     * @var array
     */
    private $stockIds;

    /**
     * @var array
     */
    private $skuRelations;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Constructor to inject class dependencies
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Initialize stock ids and relations
     *
     * @return void
     */
    public function _construct(): void
    {
        $this->stockIds = [];
        $this->skuRelations = [];
        $this->stockStatus = null;
    }

    /**
     * Get stock status
     *
     * @param string|null $websiteCode
     * @return array
     */

    public function getStockStatus(?string $websiteCode) : array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->resourceConnection->getTableName('inventory_stock_' . $this->getStockId($websiteCode)),
                ['sku', 'is_salable']
            )
            ->where('sku IN (?)', $this->getSkuRelation());

        return $connection->fetchPairs($select);
    }

    /**
     * Get stock id by website code
     *
     * @param string $websiteCode
     * @return int
     */
    public function getStockId(string $websiteCode): int
    {
        if (!isset($this->stockIds[$websiteCode])) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName('inventory_stock_sales_channel'), ['stock_id'])
                ->where('type = \'website\' AND code = ?', $websiteCode);

            $this->stockIds[$websiteCode] = (int)$connection->fetchOne($select);
        }

        return (int)$this->stockIds[$websiteCode];
    }

    /**
     * Store product id and sku relations in initialized variable
     *
     * @param array $entityIds
     * @return Inventory
     */
    public function saveRelation(array $entityIds): Inventory
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            $this->resourceConnection->getTableName('catalog_product_entity'),
            ['entity_id', 'sku']
        )->where('entity_id IN (?)', $entityIds);

        $this->skuRelations = $connection->fetchPairs($select);

        return $this;
    }

    /**
     * Clean the relation
     *
     * @return void
     */
    public function clearRelation(): void
    {
        $this->skuRelations = null;
        $this->stockStatus = null;
    }

    /**
     * Get skus relation
     *
     * @return array
     */
    public function getSkuRelation(): array
    {
        return $this->skuRelations;
    }
}

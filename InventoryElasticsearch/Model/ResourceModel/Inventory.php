<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resourceConnection = $resource;
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
    }

    /**
     * Get stock status
     *
     * @param string $productSku
     * @param string|null $websiteCode
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStockStatus(string $productSku, ?string $websiteCode): int
    {
        if (!isset($this->stockStatus[$websiteCode][$productSku])) {
            $select = $this->resourceConnection->getConnection()->select()
                ->from($this->resourceConnection->getTableName('inventory_stock_' . $this->getStockId($websiteCode)), ['is_salable'])
                ->where('sku = ?', $productSku)
                ->group('sku');
            $this->stockStatus[$websiteCode][$productSku] = (int) $this->resourceConnection->getConnection()->fetchOne($select);
        }

        return (int)$this->stockStatus[$websiteCode][$productSku];
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
            $select = $this->resourceConnection->getConnection()->select()
                ->from($this->resourceConnection->getTableName('inventory_stock_sales_channel'), ['stock_id'])
                ->where('type = \'website\' AND code = ?', $websiteCode);

            $this->stockIds[$websiteCode] = (int)$this->resourceConnection->getConnection()->fetchOne($select);
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
        $select = $this->resourceConnection->getConnection()->select()->from(
            $this->resourceConnection->getTableName('catalog_product_entity'),
            ['entity_id', 'sku']
        )->where('entity_id IN (?)', $entityIds);

        $this->skuRelations = $this->resourceConnection->getConnection()->fetchPairs($select);

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
    }

    /**
     * Get sku relation
     *
     * @param int $entityId
     * @return string
     */
    public function getSkuRelation(int $entityId): string
    {
        return $this->skuRelations[$entityId] ?? '';
    }
}

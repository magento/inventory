<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

class Inventory
{
    /**
     * @var array
     */
    private $skuRelations = [];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StockResolverInterface $stockResolver
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Get stock status
     *
     * @param string $websiteCode
     * @return array
     * @throws NoSuchEntityException
     */

    public function getStockStatus(string $websiteCode) : array
    {
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->resourceConnection->getTableName('inventory_stock_' . $stock->getStockId()),
                ['sku', 'is_salable']
            )
            ->where('sku IN (?)', $this->getSkuRelation());

        return $connection->fetchPairs($select);
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
     * Get productList with productId skus relation
     *
     * @return array
     */
    public function getSkuRelation(): array
    {
        return $this->skuRelations;
    }
}

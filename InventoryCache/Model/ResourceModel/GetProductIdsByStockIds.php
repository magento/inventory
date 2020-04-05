<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Get product ids for given stock form index table.
 */
class GetProductIdsByStockIds
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
     * @var string
     */
    private $productTableName;

    /**
     * @param ResourceConnection $resource
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param string $productTableName
     */
    public function __construct(
        ResourceConnection $resource,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        string $productTableName
    ) {
        $this->resource = $resource;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->productTableName = $productTableName;
    }

    /**
     * Get product ids for given stock form index table.
     *
     * @param array $stockIds
     * @return array
     */
    public function execute(array $stockIds): array
    {
        $productIds = [[]];
        foreach ($stockIds as $stockId) {
            $stockIndexTableName = $this->stockIndexTableNameResolver->execute($stockId);
            $connection = $this->resource->getConnection();

                $sql = $connection->select()
                    ->from(['stock_index' => $stockIndexTableName], [])
                    ->join(
                        ['product' => $this->resource->getTableName($this->productTableName)],
                        'product.sku = stock_index.' . IndexStructure::SKU,
                        ['product.entity_id']
                    );
                $productIds[] = $connection->fetchCol($sql);
        }
        $productIds = array_merge(...$productIds);

        return array_unique($productIds);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryBundleProductIndexer\Indexer\SelectBuilder;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Bundle products for given stock provider.
 */
class IndexDataByStockIdProvider
{
    /**
     * @var SelectBuilder
     */
    private $selectBuilder;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param SelectBuilder $selectBuilder
     * @param ResourceConnection $resourceConnection
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        SelectBuilder $selectBuilder,
        ResourceConnection $resourceConnection,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->selectBuilder = $selectBuilder;
        $this->resourceConnection = $resourceConnection;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Get bundle products for given stock id.
     *
     * @param int $stockId
     * @return \ArrayIterator
     * @throws \Exception
     */
    public function execute(int $stockId): \ArrayIterator
    {
        $select = $this->selectBuilder->execute($stockId);
        $connection = $this->resourceConnection->getConnection();
        $results = $connection->fetchAll($select);
        $bundleSkus = array_column($results, 'sku');
        $salableResults = $this->areProductsSalable->execute($bundleSkus, $stockId);
        foreach ($salableResults as $salableResult) {
            foreach ($results as &$result) {
                if ($salableResult->getSku() === $result['sku']) {
                    $result['is_salable'] = (string)(int)$salableResult->isSalable();
                }
            }
        }

        return new \ArrayIterator($results);
    }
}

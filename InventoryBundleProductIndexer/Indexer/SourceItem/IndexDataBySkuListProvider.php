<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryBundleProductIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Returns all data for the index by source item list condition.
 */
class IndexDataBySkuListProvider
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SelectBuilder
     */
    private $selectBuilder;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param ResourceConnection $resourceConnection
     * @param SelectBuilder $selectBuilder
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        SelectBuilder $selectBuilder,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->selectBuilder = $selectBuilder;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Get index data by stock id and sku list.
     *
     * @param int $stockId
     * @param array $skuList
     * @return \ArrayIterator
     * @throws \Exception
     */
    public function execute(int $stockId, array $skuList): \ArrayIterator
    {
        $select = $this->selectBuilder->execute($stockId);
        if (count($skuList)) {
            $select->where('stock.' . IndexStructure::SKU . ' IN (?)', $skuList);
        }
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

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Integration\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;

class RemoveIndexData
{
    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var
     */
    private $indexNameResolver;

    /**
     * RemoveIndexData constructor.
     * @param IndexNameBuilder $indexNameBuilder
     * @param ResourceConnection $resourceConnection
     * @param IndexNameResolverInterface $indexNameResolver
     */
    public function __construct(
        IndexNameBuilder $indexNameBuilder,
        ResourceConnection $resourceConnection,
        IndexNameResolverInterface $indexNameResolver
    ) {
        $this->indexNameBuilder = $indexNameBuilder;
        $this->resourceConnection = $resourceConnection;
        $this->indexNameResolver = $indexNameResolver;
    }

    /**
     * @param array $stockIds
     * @return void
     */
    public function execute(array $stockIds)
    {
        foreach ($stockIds as $stockId) {
            $indexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$stockId)
                ->setAlias(Alias::ALIAS_MAIN)
                ->build();

            $connection = $this->resourceConnection->getConnection(ResourceConnection::DEFAULT_CONNECTION);
            $tableName = $this->indexNameResolver->resolveName($indexName);
            $connection->truncateTable($this->resourceConnection->getTableName($tableName));
        }
    }
}

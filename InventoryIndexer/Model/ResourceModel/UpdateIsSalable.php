<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;

/**
 * Update salability status for the product.
 */
class UpdateIsSalable
{
    /**
     * @var IndexNameResolverInterface
     */
    private $indexNameResolver;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param IndexNameResolverInterface $indexNameResolver
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        IndexNameResolverInterface $indexNameResolver,
        ResourceConnection $resourceConnection
    ) {
        $this->indexNameResolver = $indexNameResolver;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Update index salability data.
     *
     * @param IndexName $indexName
     * @param array $dataForUpdate = ['sku' => bool]
     * @param string $connectionName
     *
     * @return void
     */
    public function execute(IndexName $indexName, array $dataForUpdate, string $connectionName): void
    {
        $connection = $this->resourceConnection->getConnection($connectionName);
        $tableName = $this->indexNameResolver->resolveName($indexName);
        foreach ($dataForUpdate as $sku => $isSalable) {
            $connection->update($tableName, [IndexStructure::IS_SALABLE => $isSalable], ['sku = ?' => $sku]);
        }
    }
}

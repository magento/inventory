<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer;

use ArrayIterator;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\InventoryIndexer\Indexer\IndexHandler;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;
use PHPUnit\Framework\TestCase;

/**
 * @see IndexHandler
 */
class IndexHandlerTest extends TestCase
{
    /**
     * Integer SKUs are passed to the DELETE condition as strings.
     *
     * @return void
     */
    public function testCleanIndexDeletesSkusAsStrings(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects($this->once())
            ->method('delete')
            ->willReturnCallback(
                function (string $table, array $where): int {
                    $this->assertSame('inventory_stock_2', $table);
                    $this->assertSame(['sku IN (?)' => ['123', 'ABC', '0123']], $where);
                    return 3;
                }
            );
        $resourceConnection = $this->createStub(ResourceConnection::class);
        $resourceConnection->method('getConnection')->willReturn($connection);
        $indexNameResolver = $this->createStub(IndexNameResolverInterface::class);
        $indexNameResolver->method('resolveName')->willReturn('inventory_stock_2');

        $indexHandler = new IndexHandler(
            $indexNameResolver,
            $this->createStub(Batch::class),
            $resourceConnection,
            100,
            $this->createStub(DeploymentConfig::class)
        );
        $indexHandler->cleanIndex(
            $this->createStub(IndexName::class),
            new ArrayIterator([123, 'ABC', '0123']),
            'default'
        );
    }
}

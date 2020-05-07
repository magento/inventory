<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Test\Unit;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexTableSwitcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for @see IndexTableSwitcher.
 */
class IndexTableSwitcherTest extends TestCase
{
    /**
     * @var IndexTableSwitcher|MockObject
     */
    private $indexTableSwitcher;

    /**
     * @var IndexName|MockObject
     */
    private $indexName;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnection;

    /**
     * @var IndexNameResolverInterface|MockObject
     */
    private $indexNameResolver;

    /**
     * @var AdapterInterface|MockObject
     */
    private $adapter;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = new ObjectManager($this);
        $this->indexName = $this->createMock(IndexName::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->indexNameResolver = $this->getMockForAbstractClass(IndexNameResolverInterface::class);
        $this->adapter = $this->getMockForAbstractClass(AdapterInterface::class);

        $this->indexTableSwitcher = $objectManager->getObject(
            IndexTableSwitcher::class,
            [
                'resourceConnection' => $this->resourceConnection,
                'indexNameResolver' => $this->indexNameResolver,
            ]
        );
    }

    public function testSwitch()
    {
        $connectionName = 'testConnection';
        $tableName = 'some_table_name';
        $toRename =
            [
                [
                    'oldName' => $tableName,
                    'newName' => $tableName . '_outdated',
                ],
                [
                    'oldName' => $tableName . '_replica',
                    'newName' => $tableName,
                ],
                [
                    'oldName' => $tableName . '_outdated',
                    'newName' => $tableName . '_replica',
                ],
            ];

        $this->resourceConnection->expects($this->once())->method('getConnection')
            ->with($connectionName)->willReturn($this->adapter);
        $this->indexNameResolver->expects($this->once())->method('resolveName')
            ->with($this->indexName)->willReturn($tableName);
        $this->adapter->expects($this->once())->method('renameTablesBatch')
            ->with($toRename);

        $this->indexTableSwitcher->switch($this->indexName, $connectionName);
    }
}

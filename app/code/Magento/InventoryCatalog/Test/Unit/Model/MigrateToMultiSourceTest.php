<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Model;

use Magento\Framework\Api\Search\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Model\DefaultSourceProvider;
use Magento\InventoryCatalog\Model\MigrateToMultiSource;
use PHPUnit\Framework\TestCase;

class MigrateToMultiSourceTest extends TestCase
{
    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var DefaultSourceProvider
     */
    private $defaultSourceProvider;

    /**
     * @var MigrateToMultiSource
     */
    private $migrateToMultiSource;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    protected function setUp()
    {
        $this->sourceItemsDelete = $this->getMockBuilder(SourceItemsDeleteInterface::class)->getMock();
        $this->sourceItemsSave = $this->getMockBuilder(SourceItemsSaveInterface::class)->getMock();
        $this->sourceItemRepository = $this->getMockBuilder(SourceItemRepositoryInterface::class)->getMock();
        $this->searchCriteriaBuilderFactory = $this->getMockBuilder(SearchCriteriaBuilderFactory::class)->getMock();
        $this->defaultSourceProvider = $this->getMockBuilder(DefaultSourceProvider::class)->getMock();
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sourceItemsSave->meuh = 'plouf';
        $this->migrateToMultiSource = (new ObjectManager($this))->getObject(
            MigrateToMultiSource::class,
            [
                'sourceItemsDelete'=> $this->sourceItemsDelete,
                'sourceItemsSave' => $this->sourceItemsSave,
                'sourceItemRepository' => $this->sourceItemRepository,
                'searchCriteriaBuilderFactory' => $this->searchCriteriaBuilderFactory,
                'defaultSourceProvider' => $this->defaultSourceProvider,
                'resourceConnection' => $this->resourceConnection
            ]
        );
    }


    public function testMigrateWhenNoSourceItemIsFound()
    {
        $this->aSearchCriteriaWillBeBuilt();
        $connection = $this->aConnectionWillBeRequested();
        $this->sourceItemsWillBeFound([]);

        $connection->expects($this->never())->method('beginTransaction');
        $this->sourceItemsDelete->expects($this->never())->method('execute');
        $this->sourceItemsSave->expects($this->never())->method('execute');

        $this->migrateToMultiSource->execute(['SKU'], 'SOURCE');
    }

    public function testMigrateWhenSourceItemsAreFound()
    {
        $sourceItem = $this->getMockBuilder(SourceItemInterface::class)->getMock();

        $this->aSearchCriteriaWillBeBuilt();
        $connection = $this->aConnectionWillBeRequested();
        $this->sourceItemsWillBeFound([$sourceItem]);

        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('commit');
        $sourceItem->expects($this->atLeastOnce())->method('setSourceCode')->with('SOURCE');
        $this->sourceItemsSave->expects($this->once())->method('execute')->with([$sourceItem]);
        $this->sourceItemsDelete->expects($this->once())->method('execute')->with([$sourceItem]);

        $this->migrateToMultiSource->execute(['SKU'], 'SOURCE');
    }

    public function testMigrateWhenSourceItemsCannotBeDeleted()
    {
        $sourceItem = $this->getMockBuilder(SourceItemInterface::class)->getMock();

        $this->aSearchCriteriaWillBeBuilt();
        $connection = $this->aConnectionWillBeRequested();
        $this->sourceItemsWillBeFound([$sourceItem]);
        $this->sourceItemsDelete->method('execute')->willThrowException(new \Exception());

        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->never())->method('commit');
        $connection->expects($this->once())->method('rollback');
        $sourceItem->expects($this->atLeastOnce())->method('setSourceCode')->with('SOURCE');

        $this->migrateToMultiSource->execute(['SKU'], 'SOURCE');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function aSearchCriteriaWillBeBuilt(): \PHPUnit_Framework_MockObject_MockObject
    {
        $searchCriteria = $this->getMockBuilder(SearchCriteriaInterface::class)->getMock();

        $searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()->getMock();
        $searchCriteriaBuilder->method('addFilter')->willReturnSelf();

        $searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $this->searchCriteriaBuilderFactory->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaBuilder);
        return $searchCriteriaBuilder;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function aConnectionWillBeRequested(): \PHPUnit_Framework_MockObject_MockObject
    {
        $connection = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $this->resourceConnection->method('getConnection')->willReturn($connection);
        return $connection;
    }

    /**
     * @param $items
     */
    private function sourceItemsWillBeFound($items): void
    {
        $sourceItemSearchResults = $this->getMockBuilder(SourceItemSearchResultsInterface::class)->getMock();
        $sourceItemSearchResults->method('getItems')->willReturn($items);
        $this->sourceItemRepository->method('getList')->willReturn($sourceItemSearchResults);
    }
}
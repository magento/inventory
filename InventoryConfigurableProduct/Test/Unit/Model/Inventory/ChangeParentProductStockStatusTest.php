<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Unit\Model\Inventory;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Inventory\Model\ResourceModel\Source\CollectionFactory;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurableProduct\Model\Inventory\ChangeParentProductStockStatus;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use PHPUnit\Framework\TestCase;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * @inheritDoc
 */
class ChangeParentProductStockStatusTest extends TestCase
{
    /**
     * @var RequestInterface
     */
    private $requestMock;

    /**
     * @var Configurable
     */
    private $configurableTypeMock;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistryMock;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepositoryMock;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepositoryMock;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIdsMock;

    /**
     * @var CollectionFactory
     */
    private $collectionFactoryMock;

    /**
     * @var ChangeParentProductStockStatus
     */
    private $changeParentProductStockStatus;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configurableTypeMock = $this->getMockBuilder(Configurable::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockRegistryMock = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceItemRepositoryMock = $this->getMockBuilder(SourceItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockItemRepositoryMock = $this->getMockBuilder(StockItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->getSkusByProductIdsMock = $this->getMockBuilder(GetSkusByProductIdsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['addFieldToFilter','create','addFieldToSelect','getColumnValues'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->changeParentProductStockStatus = $this->getMockBuilder(ChangeParentProductStockStatus::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->changeParentProductStockStatus = new ChangeParentProductStockStatus(
            $this->requestMock,
            $this->configurableTypeMock,
            $this->stockRegistryMock,
            $this->searchCriteriaBuilderMock,
            $this->sourceItemRepositoryMock,
            $this->stockItemRepositoryMock,
            $this->getSkusByProductIdsMock,
            $this->collectionFactoryMock
        );
    }

    public function testChangeParentProductStockStatus()
    {
        $parentIds = [14];
        $childproductId = 13;
        $childrenIds = [[13 => 13]];
        $childrenSkus = [13 => 'config-parent-blue'];
        $sourcecodes = [0 => 'default',1 =>'north'];
        $childrenIsInStock = false;
        $sources = [
            'assigned_sources' =>[
                [
                    'source_code' => 'north',
                    'quantity' =>1
                ]
            ]
        ];
        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($childproductId)
            ->willReturn($parentIds);

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['sources', [], $sources]
                ]
            );

        $this->collectionFactoryMock->expects($this->any())->method('create')->willReturn($this->collectionFactoryMock);
        $this->collectionFactoryMock->expects($this->any())->method('addFieldToFilter')
            ->with('enabled', 1)
            ->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('addFieldToSelect')
            ->with('source_code')
            ->willReturnSelf();
        $this->collectionFactoryMock->expects($this->any())->method('getColumnValues')
            ->with('source_code')
            ->willReturn($sourcecodes);
//
        $this->configurableTypeMock->expects($this->any())
            ->method('getChildrenIds')
            ->with($parentIds[0])
            ->willReturn($childrenIds);
        $this->getSkusByProductIdsMock->expects($this->once())
            ->method('execute')
            ->with($childrenIds[0])
            ->willReturn($childrenSkus);


        $searchCriteriaMock = $this->createMock(SearchCriteria::class);

        $this->searchCriteriaBuilderMock->expects($this->any())->method('addFilter')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects($this->any())->method('create')->willReturn($searchCriteriaMock);
        $searchResultsMock = $this->getMockForAbstractClass(SourceItemSearchResultsInterface::class);
        $this->sourceItemRepositoryMock->expects($this->any())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultsMock);

        $sourceItems = $this->getMockForAbstractClass(SourceItemInterface::class);
        $searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn($sourceItems);

        $stockItemMock = $this->getMockBuilder(StockItemInterface::class)
            ->setMethods(['setIsInStock', 'setStockStatusChangedAuto'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stockRegistryMock->expects($this->any())
            ->method('getStockItem')
            ->with($parentIds[0])
            ->willReturn($stockItemMock);
        $stockItemMock->expects($this->any())->method('setIsInStock')->with($childrenIsInStock)->willReturnSelf();
        $stockItemMock->expects($this->any())->method('setStockStatusChangedAuto')->with(1)->willReturnSelf();
        $this->stockItemRepositoryMock->expects($this->any())
            ->method('save')
            ->with($stockItemMock);
        $this->changeParentProductStockStatus->execute($childproductId);
    }
}

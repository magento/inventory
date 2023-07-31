<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Test\Unit\Model\Order;

use Magento\Catalog\Model\Product;
use Magento\Framework\Api\AbstractExtensibleObject;
use Magento\Framework\Api\ExtensionAttributesInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupSales\Model\Order\GetPickupLocationCode;
use Magento\InventoryInStorePickupSales\Model\Order\IsFulfillable;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IsFulfillableTest extends TestCase
{
    /**
     * @var IsFulfillable
     */
    private $model;

    /**
     * @var SourceItemRepositoryInterface|MockObject
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilderFactory|MockObject
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SourceRepositoryInterface|MockObject
     */
    private $sourceRepository;

    /**
     * @var GetPickupLocationCode|MockObject
     */
    private $getPickupLocationCode;

    /**
     * @var OrderInterface|MockObject
     */
    private $orderMock;

    /**
     * @var Item|MockObject
     */
    private $itemMock;

    /**
     * @var Product|MockObject
     */
    private $productMock;

    /**
     * @var ExtensionAttributesInterface|MockObject
     */
    private $extensionAttributesMock;

    /**
     * @var SourceInterface|MockObject
     */
    private $sourceMock;

    /**
     * @var Item|MockObject
     */
    private $stockItemMock;

    /**
     * @var SourceItemSearchResultsInterface|MockObject
     */
    private $sourceItemSearchResultsInterface;

    /**
     * @var AbstractExtensibleObject|MockObject
     */
    private $abstractExtensibleObject;

    protected function setUp(): void
    {
        $this->sourceItemRepository = $this->getMockBuilder(SourceItemRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList'])
            ->getMock();

        $this->sourceRepository = $this->getMockBuilder(SourceRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->getPickupLocationCode = $this->getMockBuilder(GetPickupLocationCode::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getHasChildren', 'getQtyOrdered', 'getSku', 'getProduct'])
            ->getMock();

        $this->productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extensionAttributesMock = $this->getMockBuilder(ExtensionAttributesInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStockItem'])
            ->getMock();

        $this->sourceMock = $this->getMockBuilder(SourceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockItemMock = $this->getMockBuilder(\Magento\CatalogInventory\Model\Stock\Item::class)
            ->disableOriginalConstructor()
            ->setMethods(['getManageStock', 'getIsInStock'])
            ->getMock();

        $this->searchCriteriaBuilderFactory = $this
            ->getMockBuilder(SearchCriteriaBuilderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->sourceItemSearchResultsInterface = $this
            ->getMockBuilder(SourceItemSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->abstractExtensibleObject = $this
            ->getMockBuilder(AbstractExtensibleObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuantity', 'getStatus'])
            ->getMock();

        $this->model = new IsFulfillable(
            $this->sourceItemRepository,
            $this->searchCriteriaBuilderFactory,
            $this->sourceRepository,
            $this->getPickupLocationCode
        );
    }

    /**
     * Test the execute method of IsFulfillable model.
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->getPickupLocationCode->expects($this->once())
            ->method('execute')
            ->with($this->orderMock)
            ->willReturn('default');

        $this->orderMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$this->itemMock]);

        $this->itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->productMock->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);

        $this->sourceRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->sourceMock);

        $this->stockItemMock->expects($this->once())
            ->method('getManageStock')
            ->willReturn(false);

        $this->stockItemMock->expects($this->any())
            ->method('getIsInStock')
            ->willReturn(true);

        $this->extensionAttributesMock->expects($this->any())
            ->method('getStockItem')
            ->willReturn($this->stockItemMock);

        $this->itemMock->expects($this->once())
            ->method('getHasChildren')
            ->willReturn(false);

        $this->sourceMock->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->itemMock->expects($this->any())
            ->method('getSku')
            ->willReturn('SKU-1');

        $this->itemMock->expects($this->any())
            ->method('getQtyOrdered')
            ->willReturn(1);

        $searchCriteriaMock = $this
            ->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $searchCriteriaBuilderMock = $this
            ->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'addFilter'])
            ->getMock();

        $this->searchCriteriaBuilderFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteriaBuilderMock);

        $searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilter')
            ->willReturn($searchCriteriaBuilderMock);

        $searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $this->sourceItemRepository->expects($this->any())
            ->method('getList')
            ->willReturn($this->sourceItemSearchResultsInterface);

        $this->sourceItemSearchResultsInterface->expects($this->any())
            ->method('getTotalCount')
            ->willReturn(1);

        $this->sourceItemSearchResultsInterface->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->abstractExtensibleObject]);

        $this->abstractExtensibleObject->expects($this->any())
            ->method('getQuantity')
            ->willReturn(0);

        $this->abstractExtensibleObject->expects($this->any())
            ->method('getStatus')
            ->willReturn(1);

        // Assertions to check the result.
        $this->assertTrue($this->model->execute($this->orderMock));
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Plugin\Catalog\Block\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogInventory\Api\Data\StockInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryCatalog\Plugin\Catalog\Block\ProductList\UpdateToolbarCount;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;

/**
 * Test class for Update toolbar count plugin
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateToolbarCountTest extends TestCase
{
    /**
     * @var UpdateToolbarCount
     */
    private $model;

    /**
     * @var Mysql|MockObject
     */
    private $connectionMock;

    /**
     * @var AbstractCollection|MockObject
     */
    private $collectionMock;

    /**
     * @var Select|MockObject
     */
    private $selectMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $configMock;

    /**
     * @var Toolbar|MockObject
     */
    private $toolbarMock;

    /**
     * @var Data|MockObject
     */
    private $categoryHelperMock;

    /**
     * @var CategoryFactory|MockObject
     */
    private $categoryFactoryMock;

    /**
     * @var Category|MockObject
     */
    private $categoryMock;

    /**
     * @var StockRegistryInterface|MockObject
     */
    private $stockRegistryMock;

    /**
     * @var StockConfigurationInterface|MockObject
     */
    private $stockConfigurationMock;

    /**
     * @var AreProductsSalableInterface|MockObject
     */
    private $areProductsSalableMock;

    /**
     * @var StockInterface|MockObject
     */
    private $stockMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->configMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->connectionMock = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this->createMock(Select::class);
        $this->stockMock = $this->getMockBuilder(StockInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockRegistryMock = $this->getMockBuilder(StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockConfigurationMock = $this->getMockBuilder(StockConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->areProductsSalableMock = $this->createMock(AreProductsSalableInterface::class);
        $this->toolbarMock = $this->createMock(Toolbar::class);
        $this->categoryMock = $this->createMock(Category::class);
        $this->categoryHelperMock = $this->createMock(Data::class);
        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock = $this->getMockBuilder(AbstractCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->toolbarMock
            ->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->collectionMock);
        $this->categoryHelperMock
            ->expects($this->any())
            ->method('getCategory')
            ->willReturn($this->categoryMock);
        $this->categoryFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->categoryMock);
        $this->categoryMock
            ->expects($this->any())
            ->method('load')
            ->willReturnSelf();
        $this->categoryMock
            ->expects($this->any())
            ->method('getProductCollection')
            ->willReturn($this->collectionMock);
        $websiteMock = $this->createMock(WebsiteInterface::class);
        $websiteMock
            ->expects($this->any())
            ->method('getCode')
            ->willReturn('1');
        $this->storeManagerMock
            ->expects($this->any())
            ->method('getWebsite')
            ->willReturn($websiteMock);
        $this->model = $objectManager->getObject(
            UpdateToolbarCount::class,
            [
                'config' => $this->configMock,
                'categoryHelper' => $this->categoryHelperMock,
                'categoryFactory' => $this->categoryFactoryMock,
                'stockRegistry' => $this->stockRegistryMock,
                'stockConfiguration' => $this->stockConfigurationMock,
                'areProductsSalable' => $this->areProductsSalableMock,
                'storeManager' => $this->storeManagerMock
            ]
        );
    }

    /**
     * Test case to check afterGetTotalNum returns valid result
     *
     * @param array $items
     * @param bool $outOfStockFlagValue
     * @param int $defaultStockId
     * @param int $actualResult
     * @param int $expectedResult
     * @dataProvider dataProviderForAfterGetTotalNum
     * @throws LocalizedException
     */
    public function testAfterGetTotalNumReturnValidResult(
        array $items,
        bool $outOfStockFlagValue,
        int $defaultStockId,
        int $actualResult,
        int $expectedResult
    ): void {
        $this->stockRegistryMock
            ->expects($this->any())
            ->method('getStock')
            ->with($defaultStockId)
            ->willReturn($this->stockMock);
        $this->stockMock
            ->expects($this->any())
            ->method('getStockId')
            ->willReturn(1);
        $this->categoryMock
            ->expects($this->any())
            ->method('getEntityId')
            ->willReturn('2');
        $this->collectionMock->expects($this->any())
            ->method('getItems')
            ->willReturn($items);
        $this->configMock
            ->expects($this->any())
            ->method('getValue')
            ->with('cataloginventory/options/show_out_of_stock')
            ->willReturn($outOfStockFlagValue);

        $updatedResult = $this->model->afterGetTotalNum($this->toolbarMock, $actualResult);
        $this->assertEquals($expectedResult, $updatedResult);
    }

    /**
     * dataProvider for afterGetTotalNum function
     *
     * @return array
     */
    public function dataProviderForAfterGetTotalNum(): array
    {
        $item1 = new DataObject(['id' => 1, 'sku' => 'item1']);
        $item2 = new DataObject(['id' => 2, 'sku' => 'item2']);
        $item3 = new DataObject(['id' => 3, 'sku' => 'item3']);
        $item4 = new DataObject(['id' => 4, 'sku' => 'item4']);
        return [
            'verify total number of products when OUT OF STOCK status YES' => [[$item1, $item2], true, 1, 2, 2],
            'verify total number of products when OUT OF STOCK status NO' => [[$item2,$item3,$item4], false, 1, 4, 4],
            'verify total number of products when category is empty and OUT OF STOCK status YES' => [[], true, 1, 0, 0]
        ];
    }
}

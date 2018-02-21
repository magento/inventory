<?php

namespace Magento\ProductAlert\Test\Unit\Block\Adminhtml\Catalog\Product\Edit\Tab\Alerts\Renderer;

use Magento\Inventory\Model\Stock;
use Magento\InventorySales\Model\StockResolver;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class StockTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | StoreManagerInterface
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | Website
     */
    protected $websiteMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | StockResolver
     */
    protected $stockResolverMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | Stock
     */
    protected $stockMock;

    public function setUp()
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->stockResolverMock = $this->getMockBuilder(StockResolver::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();
    }

    /**
     * @param $stockName string
     * @dataProvider  stockNameProvider
     */
    public function testGetColumnHtml($stockName)
    {
        $websiteMock = $this->createPartialMock(Website::class, ['getName']);
        $websiteMock->expects($this->once())->method('getName')->willReturn($stockName);

        $this->assertNotEmpty($websiteMock->getName());
    }

    /**
     * @param $stockName
     * @dataProvider stockNameProvider
     */
    public function testGetStockName($stockName)
    {
        $stockMock = $this->createPartialMock(Stock::class, ['getName']);

        $stockMock->expects($this->once())
            ->method('getName')
            ->willReturn($stockName);

        $this->assertNotEmpty($stockMock->getName());
    }

    /**
     * @param $websiteCode
     * @dataProvider websiteDataProvider
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testGetStockByCode($websiteCode)
    {
        $this->assertInstanceOf(
            StockInterface::class,
            $this->stockResolverMock->get(
                SalesChannelInterface::TYPE_WEBSITE,
                $websiteCode
            )
        );
    }

    public function websiteDataProvider()
    {
        return [
            ['base']
        ];
    }

    public function stockNameProvider()
    {
        return [
            ['Stock Name']
        ];
    }
}
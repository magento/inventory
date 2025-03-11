<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Plugin\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter;

use Magento\InventoryCatalog\Plugin\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter\StockDataFilterPlugin;// phpcs:ignore
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * phpcs:disable
 * @covers \Magento\InventoryCatalog\Plugin\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter\StockDataFilterPlugin
 * phpcs:enable
 */
class StockDataFilterPluginTest extends TestCase
{
    /**
     * @var StockDataFilter|MockObject
     */
    private $subjectMock;

    /**
     * @var StockDataFilterPlugin
     */
    protected $stockDataFilterPlugin;

    protected function setUp(): void
    {
        $this->subjectMock = $this->getMockBuilder(StockDataFilter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockDataFilterPlugin = new StockDataFilterPlugin();
    }

    /**
     * Test for min_qty, qty filter
     */
    public function testAfterFilter()
    {
        $result = [
            'min_qty' => -1,
            'max_sale_qty' => 10000,
            'notify_stock_qty' => 1,
            'min_sale_qty' => 1,
            'manage_stock' => 1,
        ];
        $stockData = [
            'min_qty' => 1
        ];
        $result = $this->stockDataFilterPlugin->afterFilter($this->subjectMock, $result, $stockData);
        $this->assertEquals(1, $result['min_qty']);
        $this->assertEquals(0, $result['qty']);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Test\Integration\Model;

use Magento\InventoryConfiguration\Model\GetStockItemConfiguration;
use Magento\InventoryConfiguration\Model\StockItemConfiguration;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetStockItemConfigurationTest extends TestCase
{
    /**
     * @var GetStockItemConfiguration
     */
    private $getStockItemConfiguration;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->getStockItemConfiguration = Bootstrap::getObjectManager()->get(GetStockItemConfiguration::class);
    }

    /**
     * @magentoDataFixture Magento/Quote/_files/is_not_salable_product.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_item_for_not_salable_product.php
     */
    public function testExecuteWithDisabledProduct()
    {
        $stockItem = $this->getStockItemConfiguration->execute('simple-99', 10);

        self::assertInstanceOf(StockItemConfiguration::class, $stockItem);
    }
}

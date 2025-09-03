<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\InventoryCatalog\Model\GetSourceItemsBySkuAndSourceCodes;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetSourceItemsBySkuAndSourceCodesTest extends TestCase
{
    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     */
    public function testExecuteSkuAssignedToSources()
    {
        $getSourceItems = Bootstrap::getObjectManager()->get(GetSourceItemsBySkuAndSourceCodes::class);
        $items = $getSourceItems->execute('SKU-1', ['eu-1', 'eu-2']);
        $this->assertEquals(2, count($items));
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     */
    public function testExecuteSkuNotAssignedToSources()
    {
        $getSourceItems = Bootstrap::getObjectManager()->get(GetSourceItemsBySkuAndSourceCodes::class);
        $items = $getSourceItems->execute('SKU-2', ['eu-1']);
        $this->assertEmpty($items);
    }
}

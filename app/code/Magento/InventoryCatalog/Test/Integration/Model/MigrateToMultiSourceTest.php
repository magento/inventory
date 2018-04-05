<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\Model;

use Magento\InventoryCatalog\Model\MigrateToMultiSource;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Inventory\Model\ResourceModel\GetAssignedStockIdsBySku;

class MigrateToMultiSourceTest extends TestCase
{
    /**
     * @var MigrateToMultiSource
     */
    private $migrateToMultiSource;

    protected function setUp()
    {
        parent::setUp();

        $this->migrateToMultiSource = Bootstrap::getObjectManager()->get(MigrateToMultiSource::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source.php
     */
    public function testExecute()
    {
        $this->migrateToMultiSource(['SKU-1', 'SKU-2'], 'source-code-1');
    }
}
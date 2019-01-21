<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Test\Integration\Model\Adapter\Mysql\Aggregation\DataProvider;

use Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\DataProvider\SelectBuilderForAttribute\
ApplyStockConditionToSelect;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Exception\LocalizedException;

class ApplyStockConditionToSelectWithDefaultStockTest extends TestCase
{
    /**
     * @var ApplyStockConditionToSelect
     */
    private $applyStockConditionToSelect;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $this->applyStockConditionToSelect = Bootstrap::getObjectManager()->get(ApplyStockConditionToSelect::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute()
    {
        $connection = $this->resource->getConnection();
        $catalogProductIndexEavSelect = $connection->select();
        $catalogProductIndexEavSelect->from(['main_table' => $this->resource->getTableName('catalog_product_index_eav')]);
        $catalogProductIndexEav = serialize($connection->fetchAll($catalogProductIndexEavSelect));

        $cataloginventoryStockStatusSlct = $connection->select();
        $cataloginventoryStockStatusSlct->from(['main_table' => $this->resource->getTableName('cataloginventory_stock_status')]);
        $cataloginventoryStockStatus = serialize($connection->fetchAll($cataloginventoryStockStatusSlct));

        throw new LocalizedException(__('CatalogProductIndxEav: %1; cataloginventoryStockStatus: %2', $catalogProductIndexEav, $cataloginventoryStockStatus));

        $this->applyStockConditionToSelect->execute($select);
        self::assertEquals(3, count($select->query()->fetchAll()));
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Test\Integration\Model\Search\FilterMapper\TermDropdownStrategy;

use Magento\Catalog\Model\Indexer\Product\Eav\Processor;
use Magento\CatalogSearch\Model\Search\FilterMapper\TermDropdownStrategy\ApplyStockConditionToSelect;
use Magento\Framework\App\ResourceConnection;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ApplyStockConditionToSelectOnDefaultStockTest extends TestCase
{
    /**
     * @var ApplyStockConditionToSelect
     */
    private $applyStockConditionToSelect;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->applyStockConditionToSelect = Bootstrap::getObjectManager()->get(ApplyStockConditionToSelect::class);
        $this->indexer = Bootstrap::getObjectManager()->get(Indexer::class);
        $this->resourceConnection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     */
    public function testExecute()
    {
        $this->clearIndex();
        $this->indexer->load(Processor::INDEXER_ID);
        $this->indexer->reindexAll();

        $select = $this->resourceConnection->getConnection()->select();
        $select->from(
            [
                'eav_index' => $this->resourceConnection->getTableName('catalog_product_index_eav'),
            ],
            'entity_id'
        );
        $this->applyStockConditionToSelect->execute('eav_index', 'eav_index_stock', $select);

        $result = $select->query()->fetchAll();

        self::assertEquals(3, count($result));
    }

    /**
     * Clear index data
     */
    private function clearIndex()
    {
        $this->resourceConnection->getConnection()->delete(
            $this->resourceConnection->getTableName('catalog_product_index_eav')
        );

        $actualResult = $this->resourceConnection->getConnection()->fetchOne(
            $this->resourceConnection->getConnection()->select()->from(
                $this->resourceConnection->getTableName('catalog_product_index_eav'),
                'entity_id'
            )
        );
        $this->assertFalse($actualResult);
    }
}

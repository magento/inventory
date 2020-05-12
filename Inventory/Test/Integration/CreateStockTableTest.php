<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;

class CreateStockTableTest extends TestCase
{
    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;
    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->indexNameBuilder = Bootstrap::getObjectManager()->get(IndexNameBuilder::class);
        $this->indexStructure  = Bootstrap::getObjectManager()->get(IndexStructureInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDbIsolation disabled
     */
    public function testExecute()
    {
        $mainIndexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', '10')
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();

        self::assertTrue($this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION));
    }
}

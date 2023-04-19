<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\Model\ResourceModel\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple;
use Magento\Inventory\Model\SourceItem;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SaveMultipleTest extends TestCase
{
    /**
     * @var SaveMultiple
     */
    private $subject;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->subject = $this->objectManager->get(SaveMultiple::class);
        $resourceConnection = $this->objectManager->get(ResourceConnection::class);
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testExecuteUpdateSourceItemAutoIncrement()
    {
        $tableStatus = $this->connection->showTableStatus('inventory_source_item');
        $initialAutoIncrement = $tableStatus['Auto_increment'];

        $sourceItem = $this->objectManager->create(SourceItem::class);
        $sourceItem->setData([
           'sku' => 'simple',
           'source_code' => 'default',
           'quantity' => 700.0000,
           'status' => 1,
        ]);

        $this->subject->execute([$sourceItem]);
        $tableStatus = $this->connection->showTableStatus('inventory_source_item');
        $this->assertSame($tableStatus['Auto_increment'], $initialAutoIncrement);
    }
}

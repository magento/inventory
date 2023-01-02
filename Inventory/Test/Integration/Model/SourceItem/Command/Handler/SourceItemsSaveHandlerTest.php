<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\Model\SourceItem\Command\Handler;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test SourceItemsSaveHandler
 */
class SourceItemsSaveHandlerTest extends TestCase
{
    /**
     * @var SourceItemsSaveHandler
     */
    private $sourceItemsSaveHandler;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->sourceItemsSaveHandler = $objectManager->get(SourceItemsSaveHandler::class);
        $this->sourceItemFactory = $objectManager->get(SourceItemInterfaceFactory::class);
        $this->defaultSourceProvider = $objectManager->get(DefaultSourceProviderInterface::class);
    }

    /**
     * Make sure exception is thrown when max auto_increment in inventory_source_item is reached
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDbIsolation disabled
     */
    public function testMaxAutoIncrementIsReached(): void
    {
        $this->expectException(CouldNotSaveException::class);
        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = Bootstrap::getObjectManager()->get(ResourceConnection::class);

        //insert record with maximum increment to inventory_source_item table
        $tableName = $resourceConnection->getTableName('inventory_source_item');
        $maxIncrementValue = 4294967295;

        $resourceConnection->getConnection()->insert($tableName, [
            SourceItem::ID_FIELD_NAME        => $maxIncrementValue,
            SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
            SourceItemInterface::SKU         => 'dummy-sku',
            SourceItemInterface::QUANTITY    => 100,
            SourceItemInterface::STATUS      => 1
        ]);

        $sourceItemParams = [
            'data' => [
                SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
                SourceItemInterface::SKU         => 'SKU-1',
                SourceItemInterface::QUANTITY    => 100,
                SourceItemInterface::STATUS      => 1
            ]
        ];

        $sourceItem = $this->sourceItemFactory->create($sourceItemParams);

        $this->sourceItemsSaveHandler->execute([$sourceItem]);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        /** @var ResourceConnection $resourceConnection */
        $resourceConnection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $sourceItemTableName = $resourceConnection->getTableName('inventory_source_item');

        //reset auto_increment
        $resourceConnection->getConnection()
            ->delete($sourceItemTableName, [SourceItemInterface::SKU . ' = (?)' => 'dummy-sku']);
        $maxSourceItemIdQuery = sprintf("select max(%s) from %s", SourceItem::ID_FIELD_NAME, $sourceItemTableName);
        $maxSourceItemId = $resourceConnection->getConnection()->fetchOne($maxSourceItemIdQuery);
        $autoIncrementValue = (int)$maxSourceItemId + 1;
        $setIncrementQuery = sprintf("ALTER TABLE %s AUTO_INCREMENT = %s", $sourceItemTableName, $autoIncrementValue);
        $resourceConnection->getConnection()->query($setIncrementQuery);

        parent::tearDown();
    }
}

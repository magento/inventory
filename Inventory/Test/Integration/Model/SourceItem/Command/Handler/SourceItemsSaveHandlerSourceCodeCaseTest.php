<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\Model\SourceItem\Command\Handler;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests that a source item saved with a source code differing only by case is persisted
 * under the canonical source code stored in inventory_source.
 *
 * @magentoAppArea adminhtml
 */
class SourceItemsSaveHandlerSourceCodeCaseTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->sourceItemsSave = Bootstrap::getObjectManager()->create(SourceItemsSaveInterface::class);
        $this->getSourceItemsBySku = Bootstrap::getObjectManager()->create(GetSourceItemsBySkuInterface::class);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'warehouse_bravo'], 'source'),
        DataFixture(ProductFixture::class, [], 'product'),
    ]
    public function testSourceItemIsStoredUnderCanonicalSourceCodeCase(): void
    {
        $sku = $this->fixtures->get('product')->getSku();

        $dataObjectHelper = Bootstrap::getObjectManager()->create(DataObjectHelper::class);
        $data = [
            SourceItemInterface::SOURCE_CODE => 'Warehouse_Bravo',
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::QUANTITY => 25,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ];

        $sourceItemFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
        $sourceItem = $sourceItemFactory->create();
        $dataObjectHelper->populateWithArray($sourceItem, $data, SourceItemInterface::class);

        $this->sourceItemsSave->execute([$sourceItem]);

        $storedSourceItems = $this->getSourceItemsBySku->execute($sku);
        $storedSourceCodes = array_map(
            static fn (SourceItemInterface $sourceItem): string => $sourceItem->getSourceCode(),
            $storedSourceItems
        );

        self::assertContains('warehouse_bravo', $storedSourceCodes);
        self::assertNotContains('Warehouse_Bravo', $storedSourceCodes);
    }
}

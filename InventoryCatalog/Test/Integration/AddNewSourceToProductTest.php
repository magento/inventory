<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class AddNewSourceToProductTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_with_source_link.php
     */
    #[
        DbIsolation(false),
        DataFixture(ProductFixture::class, ['sku' => 'simple'], 'p1'),
    ]
    public function testAddNewSourceToProduct()
    {
        /** @var DataObjectHelper $dataObjectHelper */
        $dataObjectHelper = Bootstrap::getObjectManager()->create(DataObjectHelper::class);
        $data = [
            SourceItemInterface::SOURCE_CODE => 'source-code-1',
            SourceItemInterface::SKU => $this->fixtures->get('p1')->getSku(),
            SourceItemInterface::QUANTITY => 25,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ];

        /** @var SourceItemInterfaceFactory $sourceItemFactory */
        $sourceItemFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
        $sourceItem = $sourceItemFactory->create();
        $dataObjectHelper->populateWithArray($sourceItem, $data, SourceItemInterface::class);

        /** @var SourceItemsSaveInterface $sourceItemSave */
        $sourceItemSave = Bootstrap::getObjectManager()->create(SourceItemsSaveInterface::class);
        self::assertNull($sourceItemSave->execute([$sourceItem]));
    }
}

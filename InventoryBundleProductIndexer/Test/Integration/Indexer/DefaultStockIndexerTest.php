<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Test\Integration\Indexer;

use Magento\Bundle\Model\Product\Type as BundleProductType;
use Magento\Bundle\Model\ResourceModel\Indexer\Stock as BundleStockIndexer;
use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventoryReservations\Test\Fixture\Reservation as ReservationFixture;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[
    CoversClass(BundleStockIndexer::class),
    DbIsolation(false),
]
class DefaultStockIndexerTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var BundleStockIndexer
     */
    private $defaultStockIndexer;

    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->defaultStockIndexer = Bootstrap::getObjectManager()->create(BundleStockIndexer::class);
        $this->defaultStockIndexer->setTypeId(BundleProductType::TYPE_CODE);
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
    }

    #[
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple1', 'stock_item' => ['qty' => 5, 'use_config_min_qty' => 0, 'min_qty' => 2]],
            's1'
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple2', 'stock_item' => ['qty' => 5]], 's2'),
        DataFixture(ProductFixture::class, ['sku' => 'simple3', 'stock_item' => ['qty' => 3]], 's3'),
        DataFixture(ProductFixture::class, ['sku' => 'simple4', 'stock_item' => ['qty' => 3]], 's4'),
        DataFixture(
            ReservationFixture::class,
            [
                'stock_id' => 1,
                'sku' => '$s2.sku$',
                'quantity' => -2,
                'metadata' => [
                    'event_type' => 'shipment_created',
                    'object_type' => 'order',
                    'object_id' => '1',
                    'object_increment_id' => '100000001',
                ],
            ]
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => '$s1.sku$', 'qty' => 3, 'can_change_quantity' => 0],
            'link1'
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => '$s2.sku$', 'qty' => 3, 'can_change_quantity' => 0],
            'link2'
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => '$s3.sku$', 'qty' => 3, 'can_change_quantity' => 1],
            'link3'
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => '$s4.sku$', 'qty' => 3, 'can_change_quantity' => 0],
            'link4'
        ),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link1$', '$link2$']], 'opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link3$']], 'opt2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link4$'], 'required' => false], 'opt3'),
        DataFixture(
            BundleProductFixture::class,
            ['_options' => ['$opt1$', '$opt2$', '$opt3$'], 'shipment_type' => 1],
            'bundle1'
        ),

        TestWith([[], true]),
        TestWith([['simple1' => 0], true]),
        TestWith([['simple2' => 0], true]),
        TestWith([['simple4' => 0], true]),
        TestWith([['simple1' => 0, 'simple2' => 4], false]),
        TestWith([['simple1' => 4, 'simple2' => 0], false]),
        TestWith([['simple3' => 0], false]),
    ]
    public function testReindexEntity(array $childQuantities, bool $expectedStockStatus): void
    {
        $productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        foreach ($childQuantities as $sku => $qty) {
            $product = $productRepository->get($sku);
            $stockData = $product->getStockData();
            $stockData['qty'] = $qty;
            $product->setStockData($stockData);
            $productRepository->save($product);
        }

        $product = $this->fixtures->get('bundle1');
        $this->defaultStockIndexer->reindexEntity([$product->getId()]);
        $bundleStockItem = $this->getStockItemData->execute($product->getSku(), 1);
        self::assertEquals($expectedStockStatus, (bool) $bundleStockItem[GetStockItemDataInterface::IS_SALABLE]);
    }
}

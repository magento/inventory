<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Test\Integration\Model;

use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryBundleProduct\Model\IsBundleProductChildrenSalable;
use Magento\InventoryReservations\Test\Fixture\Reservation as ReservationFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[
    CoversClass(IsBundleProductChildrenSalable::class),
    DbIsolation(false),
]
class IsBundleProductChildrenSalableTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var IsBundleProductChildrenSalable
     */
    private $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $this->model = Bootstrap::getObjectManager()->create(IsBundleProductChildrenSalable::class);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle1', '_options' => []]),
    ]
    public function testExecuteEmptyBundle(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');
        $result = $this->model->execute('bundle1', $stock->getStockId());
        self::assertFalse($result);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple1', 'stock_item' => ['use_config_min_qty' => 0, 'min_qty' => 2]]
        ),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => 'simple1', 'source_code' => 'source2', 'quantity' => 3]]
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => 'simple1', 'qty' => 2, 'can_change_quantity' => 0],
            'b2link1'
        ),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b2link1$']], 'b2opt1'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle2', '_options' => ['$b2opt1$'], 'shipment_type' => 1]
        ),
    ]
    public function testExecuteChildSalableQtyLessThanSelectionQty(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');
        $result = $this->model->execute('bundle2', $stock->getStockId());
        self::assertFalse($result);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple2']),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => 'simple2', 'source_code' => 'source2', 'quantity' => 3]]
        ),
        DataFixture(
            ReservationFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sku' => 'simple2', 'quantity' => -2]
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => 'simple2', 'qty' => 2, 'can_change_quantity' => 0],
            'b3link1'
        ),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b3link1$']], 'b3opt1'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle3', '_options' => ['$b3opt1$'], 'shipment_type' => 1]
        ),
    ]
    public function testExecuteWithChildReservation(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');
        $result = $this->model->execute('bundle3', $stock->getStockId());
        self::assertFalse($result);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple2']),
        DataFixture(ProductFixture::class, ['sku' => 'simple3']),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => 'simple2', 'source_code' => 'source2', 'quantity' => 3],
                ['sku' => 'simple3', 'source_code' => 'source2', 'quantity' => 3],
            ]
        ),
        DataFixture(
            ReservationFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sku' => 'simple2', 'quantity' => -2]
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => 'simple2', 'qty' => 2, 'can_change_quantity' => 0],
            'b4link1'
        ),
        DataFixture(BundleSelectionFixture::class, ['sku' => 'simple3'], 'b4link2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b4link1$']], 'b4opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b4link2$']], 'b4opt2'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle4', '_options' => ['$b4opt1$', '$b4opt2$'], 'shipment_type' => 1]
        ),
    ]
    public function testExecuteNotAllRequiredOptionsSalable(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');
        $result = $this->model->execute('bundle4', $stock->getStockId());
        self::assertFalse($result);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple3']),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => 'simple3', 'source_code' => 'source2', 'quantity' => 3]]
        ),
        DataFixture(BundleSelectionFixture::class, ['sku' => 'simple3'], 'b5link1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b5link1$'], 'required' => false], 'b5opt1'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle5', '_options' => ['$b5opt1$'], 'shipment_type' => 1]
        ),
    ]
    public function testExecuteOneNotRequiredOption(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');
        $result = $this->model->execute('bundle5', $stock->getStockId());
        self::assertTrue($result);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple2']),
        DataFixture(ProductFixture::class, ['sku' => 'simple3']),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => 'simple2', 'source_code' => 'source2', 'quantity' => 3],
                ['sku' => 'simple3', 'source_code' => 'source2', 'quantity' => 3],
            ]
        ),
        DataFixture(
            ReservationFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sku' => 'simple2', 'quantity' => -2]
        ),
        DataFixture(
            BundleSelectionFixture::class,
            ['sku' => 'simple2', 'qty' => 2, 'can_change_quantity' => 0],
            'b6link1'
        ),
        DataFixture(BundleSelectionFixture::class, ['sku' => 'simple3'], 'b6link2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b6link1$'], 'required' => false], 'b6opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b6link2$']], 'b6opt2'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle6', '_options' => ['$b6opt1$', '$b6opt2$'], 'shipment_type' => 1]
        ),
    ]
    public function testExecuteRequiredOptionSalableNotRequiredNot(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');
        $result = $this->model->execute('bundle6', $stock->getStockId());
        self::assertTrue($result);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple1', 'stock_item' => ['use_config_min_qty' => 0, 'min_qty' => 2]]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple2']),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => 'simple1', 'source_code' => 'source2', 'quantity' => 3],
                ['sku' => 'simple2', 'source_code' => 'source2', 'quantity' => 3],
            ]
        ),
        DataFixture(
            ReservationFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sku' => 'simple2', 'quantity' => -2]
        ),
        DataFixture(BundleSelectionFixture::class, ['sku' => 'simple1'], 'b7link1'),
        DataFixture(BundleSelectionFixture::class, ['sku' => 'simple2'], 'b7link2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b7link1$']], 'b7opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$b7link2$']], 'b7opt2'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle7', '_options' => ['$b7opt1$', '$b7opt2$'], 'shipment_type' => 1]
        ),
    ]
    public function testExecuteWithReservationEnoughForSelectionQty(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');
        $result = $this->model->execute('bundle7', $stock->getStockId());
        self::assertTrue($result);
    }

    #[
        DataFixture(SourceFixture::class, ['source_code' => 'source2']),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => 'source2']]
        ),
        DataFixture(BundleOptionFixture::class, ['product_links' => []], 'b8opt1'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle8', '_options' => ['$b8opt1$']]),
    ]
    public function testExecuteEmptyOption(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');
        $result = $this->model->execute('bundle8', $stock->getStockId());
        self::assertFalse($result);
    }
}

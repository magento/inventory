<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservations\Test\Integration\Model\ResourceModel;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\InventoryReservations\Model\ResourceModel\CleanupReservations;
use Magento\InventoryReservations\Test\Fixture\Reservation as ReservationFixture;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CleanupReservationsTest extends TestCase
{
    /**
     * @var CleanupReservations
     */
    private CleanupReservations $cleanupReservations;

    protected function setUp(): void
    {
        $this->cleanupReservations = Bootstrap::getObjectManager()->create(CleanupReservations::class);
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => 'prod1', 'stock_item' => ['qty' => 90]]),
        DataFixture(
            ReservationFixture::class,
            ['stock_id' => 1, 'sku' => 'prod1', 'quantity' => -10, 'metadata' => '$dataset.1$']
        ),
        DataProvider('dataProvider', false),
    ]
    public function testExecute(int $expectedSalableQty): void
    {
        $getProductSalableQty = Bootstrap::getObjectManager()->get(GetProductSalableQtyInterface::class);
        $salableQty = $getProductSalableQty->execute('prod1', 1);
        self::assertEquals(80, $salableQty);
        $this->cleanupReservations->execute();
        $salableQty = $getProductSalableQty->execute('prod1', 1);
        self::assertEquals($expectedSalableQty, $salableQty);
    }

    /**
     * @return array
     */
    public static function dataProvider(): array
    {
        return [
            [80, ['object_increment_id' => 666666]],
            [80, ['object_increment_id' => 666666, 'event_timestamp' => time() + 86400]],
            [90, ['object_increment_id' => 666666, 'event_timestamp' => time() - 86401]],
        ];
    }
}

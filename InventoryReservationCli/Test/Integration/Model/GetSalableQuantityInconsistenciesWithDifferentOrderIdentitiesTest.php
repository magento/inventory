<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Test\Integration\Model;

use Magento\InventoryReservationCli\Model\GetSalableQuantityInconsistencies;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test to check ability to work correctly with entity id and increment id for inconsistencies
 */
class GetSalableQuantityInconsistenciesWithDifferentOrderIdentitiesTest extends TestCase
{
    /**
     * @var GetSalableQuantityInconsistencies
     */
    private $getSalableQuantityInconsistencies;

    /**
     * Initialize test dependencies
     */
    protected function setUp(): void
    {
        $this->getSalableQuantityInconsistencies
            = Bootstrap::getObjectManager()->get(GetSalableQuantityInconsistencies::class);
    }

    /**
     * Verify Reservation with only objectId in the metadata
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento_InventoryReservationCli::Test/Integration/_files/delete_reservations.php
     * @magentoDataFixture Magento_InventoryReservationCli::Test/Integration/_files/reservation_with_order_id_only.php
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function testReservationWithObjectId(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies->execute();
        self::assertSame([], $inconsistencies);
    }

    /**
     * Verify Reservation with only objectIncrementId in the metadata
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento_InventoryReservationCli::Test/Integration/_files/delete_reservations.php
     * @magentoDataFixture Magento_InventoryReservationCli::Test/Integration/_files/reservation_with_order_increment_id_only.php
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function testReservationWithObjectIncrementId(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies->execute();
        self::assertSame([], $inconsistencies);
    }
}

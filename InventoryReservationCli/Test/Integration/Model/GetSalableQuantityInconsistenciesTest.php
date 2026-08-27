<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Test\Integration\Model;

use Magento\Bundle\Test\Fixture\AddProductToCart as AddBundleProductToCartFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\ConfigurableProduct\Test\Fixture\AddProductToCart as AddConfigurableProductToCartFixture;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\GroupedProduct\Test\Fixture\AddProductToCart as AddGroupedProductToCartFixture;
use Magento\GroupedProduct\Test\Fixture\Product as GroupedProductFixture;
use Magento\InventoryReservationCli\Model\GetSalableQuantityInconsistencies;
use Magento\InventoryReservations\Test\Fixture\Reservation as ReservationFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Sales\Test\Fixture\Shipment as ShipmentFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for salable quantity inconsistencies CLI logic (many fixtures only for #[DataFixture] wiring).
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/909285/scenarios/3026032
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/888618/scenarios/3027875
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/888618/scenarios/3027429
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/888618/scenarios/3027919
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/909285/scenarios/3027919
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/909285/scenarios/3031256
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/909285/scenarios/3031505
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/888618/scenarios/3031591
 * @see https://app.hiptest.com/projects/69435/test-plan/folders/888618/scenarios/3031728
 */
class GetSalableQuantityInconsistenciesTest extends TestCase
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

    #[
        DataFixture(ProductFixture::class, ['sku' => 'simple']),
        DataFixture(
            'Magento_InventoryReservationCli::Test/Integration/_files/create_incomplete_order_with_reservation.php'
        ),
    ]
    public function testIncompleteOrderWithExistingReservation(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertSame([], $inconsistencies);
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => 'simple']),
        DataFixture(
            'Magento_InventoryReservationCli::Test/Integration/_files/create_incomplete_order_without_reservation.php'
        ),
    ]
    public function testIncompleteOrderWithoutReservation(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(1, $inconsistencies);
    }

    #[
        DataFixture(ProductFixture::class, ['sku' => 'virtual-product', 'type_id' => ProductType::TYPE_VIRTUAL]),
        DataFixture(
            'Magento_InventoryReservationCli::'
            . 'Test/Integration/_files/create_incomplete_order_without_reservation_virtual_product.php'
        ),
    ]
    public function testIncompleteOrderWithoutReservationVirtualProduct(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(1, $inconsistencies);
    }

    /**
     * Verify GetSalableQuantityInconsistencies::executeAll() won't throw error in case product sku is numeric.
     */
    #[
        DataFixture(ProductFixture::class, ['sku' => '12345']),
        DataFixture(
            'Magento_InventoryReservationCli::'
            . 'Test/Integration/_files/create_incomplete_order_without_reservation_numeric_sku.php'
        ),
    ]
    public function testIncompleteOrderWithoutReservationNumericSku(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(1, $inconsistencies);
    }

    #[
        DataFixture('Magento/Sales/_files/order.php'),
        DataFixture('Magento_InventoryReservationCli::Test/Integration/_files/order_with_reservation.php'),
    ]
    public function testCompletedOrderWithReservations(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertSame([], $inconsistencies);
    }

    #[
        DataFixture('Magento/Sales/_files/order_with_shipping_and_invoice.php'),
        DataFixture(
            ReservationFixture::class,
            [
                'stock_id' => 1,
                'sku' => 'simple',
                'quantity' => -5,
                'metadata' => [
                    'event_type' => 'shipment_created',
                    'object_type' => 'order',
                    'object_id' => '1',
                    'object_increment_id' => '100000001',
                ],
            ]
        ),
    ]
    public function testCompletedOrderWithMissingReservations(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(1, $inconsistencies);
    }

    /**
     * Verify inventory:reservations:list-inconsistencies will return correct qty for configurable product.
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/create_quote_on_us_website.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/order_configurable_product.php
     * @magentoDataFixture Magento_InventoryReservationCli::Test/Integration/_files/delete_reservations.php
     * @magentoDbIsolation disabled
     *
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/909285/scenarios/3528989
     * @return void
     */
    public function testFindMissingReservationConfigurableProductCustomStock(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        $items = reset($inconsistencies)->getItems();
        self::assertEquals(3, $items['simple_10']);
    }

    /**
     * Test inventory:reservations:list-inconsistencies will return correct result use pagination
     */
    #[
        DbIsolation(false),
        DataFixture(
            'Magento_InventoryReservationCli::Test/Integration/_files/delete_reservations.php'
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple']),
        DataFixture(
            'Magento_InventoryReservationCli::Test/Integration/_files/create_incomplete_orders_with_reservations.php'
        ),
    ]
    public function testExecuteEmptyWithPagination()
    {
        $bunchSize = 1;
        foreach ($this->getSalableQuantityInconsistencies->execute($bunchSize) as $inconsistencies) {
            self::assertEmpty($inconsistencies);
        }
    }

    /**
     * Verify inventory:reservations:list-inconsistencies will return correct items qty for a partially shipped order
     */
    #[
        DbIsolation(false),
        DataFixture('Magento_InventoryReservationCli::Test/Integration/_files/create_partially_shipped_order.php'),
        DataFixture('Magento_InventoryReservationCli::Test/Integration/_files/delete_reservations.php'),
    ]
    public function testPartiallyShippedOrderWithMissingReservations(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        $items = reset($inconsistencies)
            ->getItems();
        self::assertEquals(1, $items['simple']);
    }

    /**
     * Test inconsistencies when order is cancelled and compensation reservation deleted
     */
    #[
        DbIsolation(false),
        DataFixture('Magento/Sales/_files/order_canceled.php'),
        DataFixture('Magento_InventoryReservationCli::Test/Integration/_files/delete_reservation_canceled_order.php'),
    ]
    public function testCanceledOrderWithExistingReservation(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(1, $inconsistencies);
        $item = reset($inconsistencies)->getItems();
        self::assertEquals(-2, $item['simple']);
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attr$'], '_links' => ['$p1$', '$p2$']],
            'cp1'
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddConfigurableProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$cp1.id$', 'child_product_id' => '$p1.id$', 'qty' => 2],
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(
            ShipmentFixture::class,
            ['order_id' => '$order.id$', 'items' => [['product_id' => '$cp1.id$', 'qty' => 1]]]
        ),
    ]
    public function testPartiallyShippedConfigurableProduct(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(
            0,
            $inconsistencies,
            $inconsistencies ? json_encode(reset($inconsistencies)->getItems()) : ''
        );
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p1$']], 'opt1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$p2$']], 'opt2'),
        DataFixture(
            BundleProductFixture::class,
            ['sku' => 'bundle1', '_options' => ['$opt1$', '$opt2$']],
            'bp1'
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddBundleProductToCartFixture::class,
            [
                'cart_id' => '$cart.id$',
                'product_id' => '$bp1.id$',
                'selections' => [['$p1.id$'], ['$p2.id$']],
                'qty' => 2
            ],
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(
            ShipmentFixture::class,
            ['order_id' => '$order.id$', 'items' => [['product_id' => '$bp1.id$', 'qty' => 1]]]
        ),
    ]
    public function testPartiallyShippedBundleProduct(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(
            0,
            $inconsistencies,
            $inconsistencies ? json_encode(reset($inconsistencies)->getItems()) : ''
        );
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(ProductFixture::class, as: 'p2'),
        DataFixture(
            GroupedProductFixture::class,
            ['product_links' => [['sku' => '$p1.sku$', 'qty' => 3], ['sku' => '$p2.sku$', 'qty' => 2]]],
            'gp1'
        ),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddGroupedProductToCartFixture::class,
            [
                'cart_id' => '$cart.id$',
                'product_id' => '$gp1.id$',
                'child_products' => ['$p1.id$', '$p2.id$'],
            ],
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(
            ShipmentFixture::class,
            ['order_id' => '$order.id$', 'items' => [['product_id' => '$p1.id$'], ['product_id' => '$p2.id$']]]
        ),
    ]
    public function testPartiallyShippedGroupedProduct(): void
    {
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(
            0,
            $inconsistencies,
            $inconsistencies ? json_encode(reset($inconsistencies)->getItems()) : ''
        );
    }

    /**
     * Load current Inconsistencies
     *
     * @return array
     */
    private function getSalableQuantityInconsistencies(): array
    {
        $items = [];
        foreach ($this->getSalableQuantityInconsistencies->execute() as $bunch) {
            $items += $bunch;
        }

        return $items;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Integration\Model\Queue;

use Magento\Bundle\Test\Fixture\AddProductToCart as AddBundleProductToCartFixture;
use Magento\Bundle\Test\Fixture\Link as BundleSelectionFixture;
use Magento\Bundle\Test\Fixture\Option as BundleOptionFixture;
use Magento\Bundle\Test\Fixture\Product as BundleProductFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\MessageQueue\ConsumerInterface;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Test\Fixture\DeleteSourceItems as DeleteSourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\ClearQueueProcessor;
use PHPUnit\Framework\TestCase;

class UpdateIndexSalabilityStatusTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var ConsumerInterface
     */
    private $consumer;

    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    /**
     * @var ClearQueueProcessor
     */
    private static $clearQueueProcessor;

    public static function setUpBeforeClass(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        self::$clearQueueProcessor = $objectManager->get(ClearQueueProcessor::class);
        self::$clearQueueProcessor->execute('inventory.reservations.updateSalabilityStatus');
    }

    protected function setUp(): void
    {
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $consumerFactory = Bootstrap::getObjectManager()->get(ConsumerFactory::class);
        $this->consumer = $consumerFactory->get('inventory.reservations.updateSalabilityStatus');
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
    }

    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(SourceFixture::class, as: 'source2'),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => '$source2.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple1'], 's1'),
        DataFixture(DeleteSourceItemsFixture::class, [['sku' => '$s1.sku$', 'source_code' => 'default']]),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => '$s1.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 1]]
        ),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$s1.sku$'], 'link1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link1$']], 'opt1'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle1', '_options' => ['$opt1$']], 'b1'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(AddProductToCartFixture::class, ['cart_id' => '$cart.id$', 'product_id' => '$s1.id$']),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
    ]
    public function testProductsStatusesAfterBuyingChildProduct(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');

        $this->consumer->process(1);

        $childStockItem = $this->getStockItemData->execute('simple1', $stock->getStockId());
        self::assertFalse((bool) $childStockItem[GetStockItemDataInterface::IS_SALABLE]);
        $bundleStockItem = $this->getStockItemData->execute('bundle1', $stock->getStockId());
        self::assertFalse((bool) $bundleStockItem[GetStockItemDataInterface::IS_SALABLE]);
    }

    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(SourceFixture::class, as: 'source2'),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock2.stock_id$', 'source_code' => '$source2.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(ProductFixture::class, ['sku' => 'simple1'], 's1'),
        DataFixture(DeleteSourceItemsFixture::class, [['sku' => '$s1.sku$', 'source_code' => 'default']]),
        DataFixture(
            SourceItemsFixture::class,
            [['sku' => '$s1.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 1]]
        ),
        DataFixture(BundleSelectionFixture::class, ['sku' => '$s1.sku$'], 'link1'),
        DataFixture(BundleOptionFixture::class, ['product_links' => ['$link1$']], 'opt1'),
        DataFixture(BundleProductFixture::class, ['sku' => 'bundle1', '_options' => ['$opt1$']], 'b1'),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddBundleProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$b1.id$', 'selections' => [['$s1.id$']]],
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
    ]
    public function testProductsStatusesAfterBuyingBundleProduct(): void
    {
        /** @var StockInterface $stock */
        $stock = $this->fixtures->get('stock2');

        $this->consumer->process(2);

        $childStockItem = $this->getStockItemData->execute('simple1', $stock->getStockId());
        self::assertFalse((bool) $childStockItem[GetStockItemDataInterface::IS_SALABLE]);
        $bundleStockItem = $this->getStockItemData->execute('bundle1', $stock->getStockId());
        self::assertFalse((bool) $bundleStockItem[GetStockItemDataInterface::IS_SALABLE]);
    }

    public static function tearDownAfterClass(): void
    {
        Bootstrap::getObjectManager()->get(ConsumerFactory::class)
            ->get('inventory.reservations.updateSalabilityStatus')
            ->process(1);
    }
}

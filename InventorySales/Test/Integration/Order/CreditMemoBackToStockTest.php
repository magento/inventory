<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\Order;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\InventoryApi\Test\Fixture\SourceItem as SourceItemFixture;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetGuestEmail as SetGuestEmailFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;

/**
 * Class to test credit memo updates of the reservations
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditMemoBackToStockTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CreditmemoFactory
     */
    private $creditMemoFactory;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditMemoRepositoryInterface;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemBySku;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->creditMemoFactory = $this->objectManager->create(CreditmemoFactory::class);
        $this->creditMemoRepositoryInterface = $this->objectManager->create(CreditmemoRepositoryInterface::class);
        $this->fixtures = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->getSourceItemBySku = $this->objectManager->get(GetSourceItemsBySkuInterface::class);
    }

    #[
        DataFixture(ProductFixture::class, ['weight' => 0, 'type_id' => 'virtual'], 'p1'),
        DataFixture(SourceItemFixture::class, ['sku' => '$p1.sku$', 'source_code' => 'default', 'quantity' => 100]),
        DataFixture(GuestCartFixture::class, as: 'cart'),
        DataFixture(
            AddProductToCartFixture::class,
            ['cart_id' => '$cart.id$', 'product_id' => '$p1.id$', 'qty' => 1],
            'item1'
        ),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetGuestEmailFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], 'order'),
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$'])
    ]
    public function testCreditMemoVirtualProductDoNotReturnToStock(): void
    {
        $productSKU = $this->fixtures->get('p1')->getSku();
        $order = $this->fixtures->get('order');
        //Assert quantity of source item after invoice
        $this->assertSourceItemQuantity($productSKU, 99);

        $creditMemo = $this->creditMemoFactory->createByOrder($order);
        $this->creditMemoRepositoryInterface->save($creditMemo);

        //Assert quantity of source item after creditmemo with backToStock = false by default
        $this->assertSourceItemQuantity($productSKU, 99);
    }

    /**
     * Assert quantity of the default source item
     *
     * @param string $sku
     * @param float $expectedQty
     * @return void
     */
    private function assertSourceItemQuantity(string $sku, float $expectedQty): void
    {
        $currentQtyForDefaultSource = current($this->getSourceItemBySku->execute($sku))->getQuantity();
        self::assertEquals($expectedQty, $currentQtyForDefaultSource);
    }
}

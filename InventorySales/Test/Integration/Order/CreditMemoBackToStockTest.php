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
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

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

    /**
     * @magentoDataFixture Magento\Catalog\Test\Fixture\Product with:{"weight": 0, "type_id": "virtual"} as:p1
     * @magentoDataFixture Magento\CatalogInventory\Test\Fixture\SourceItem with:{"sku": "$p1.sku$", "source_code": "default", "quantity": 100}
     * @magentoDataFixture Magento\Quote\Test\Fixture\GuestCart as:cart
     * @magentoDataFixture Magento\Quote\Test\Fixture\AddProductToCart with:{"cart_id": "$cart.id$", "product_id": "$p1.id$", "qty": 1} as:item1
     * @magentoDataFixture Magento\Checkout\Test\Fixture\SetBillingAddress with:{"cart_id":"$cart.id$"}
     * @magentoDataFixture Magento\Checkout\Test\Fixture\SetGuestEmail with:{"cart_id":"$cart.id$"}
     * @magentoDataFixture Magento\Checkout\Test\Fixture\SetPaymentMethod with:{"cart_id":"$cart.id$"}
     * @magentoDataFixture Magento\Checkout\Test\Fixture\PlaceOrder with:{"cart_id":"$cart.id$"} as:order
     * @magentoDataFixture Magento\Sales\Test\Fixture\Invoice with:{"order_id":"$order.id$"}
     *
     * @return void
     */
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

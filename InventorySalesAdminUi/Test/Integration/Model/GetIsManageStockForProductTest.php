<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Test\Integration\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventorySalesAdminUi\Model\GetIsManageStockForProduct;
use Magento\Quote\Test\Fixture\GuestCart as GuestCartFixture;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Test\Fixture\Invoice as InvoiceFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class to test if stock is manageable for a product.
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class GetIsManageStockForProductTest extends TestCase
{
    /**
     * @var GetIsManageStockForProduct
     */
    private $getIsManageStockForProduct;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var CreditmemoFactory
     */
    private $creditMemoFactory;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditMemoRepositoryInterface;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->creditMemoFactory = $this->objectManager->create(CreditmemoFactory::class);
        $this->creditMemoRepositoryInterface = $this->objectManager->create(CreditmemoRepositoryInterface::class);
        $this->getIsManageStockForProduct = $this->objectManager->get(GetIsManageStockForProduct::class);
    }

    #[
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attr$'], '_links' => ['$p1$']],
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
        DataFixture(InvoiceFixture::class, ['order_id' => '$order.id$']),
    ]
    public function testCreditMemoForConfigurableWithDeletedSimpleProductOption(): void
    {
        $productSKU = $this->fixtures->get('p1')->getSku();
        $order = $this->fixtures->get('order');
        $this->deleteProductBySku($productSKU);
        $this->assertFalse(
            $this->getIsManageStockForProduct->execute(
                $productSKU,
                $order->getStore()->getWebsite()->getCode()
            )
        );
        $creditMemo = $this->creditMemoFactory->createByOrder($order);
        $this->creditMemoRepositoryInterface->save($creditMemo);
        self::assertGreaterThan(0, count($creditMemo->getItems()));
    }

    /**
     * Delete product by sku
     *
     * @param string $sku
     * @return void
     */
    private function deleteProductBySku(string $sku): void
    {
        try {
            $product = $this->productRepository->get($sku);
            $this->productRepository->delete($product);
        } catch (NoSuchEntityException $exception) {
            // product doesn't exist;
        }
    }
}

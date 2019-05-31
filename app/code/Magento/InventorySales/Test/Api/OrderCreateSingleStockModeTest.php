<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Web Api order create in single stock mode tests.
 */
class OrderCreateSingleStockModeTest extends WebapiAbstract
{
    const RESOURCE_PATH_CUSTOMER_TOKEN = '/V1/integration/customer/token';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Registered customer token.
     *
     * @var string
     */
    private $customerToken;

    /**
     * Registered, guest customer cart id.
     *
     * @var string|int
     */
    private $cartId;

    /**
     * Store code to make request to specific website.
     *
     * @var string
     */
    private $storeViewCode = 'default';

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Create order with different types of products - registered customer, single stock mode, default website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_simple.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryBundleProduct/Test/_files/product_bundle.php
     *
     * @return void
     */
    public function testCustomerPlaceOrderWithDifferentProductTypesDefaultWebsite(): void
    {
        $this->_markTestAsRestOnly();
        $customerTokenService = $this->objectManager->create(CustomerTokenServiceInterface::class);
        $this->customerToken = $customerTokenService->createCustomerAccessToken('customer@example.com', 'password');
        $this->cartId = $this->getCart();
        $this->addProducts();
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
    }

    /**
     * Create order with different types of products - registered customer, single stock mode, custom website.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_simple.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryBundleProduct/Test/_files/product_bundle.php
     *
     * @return void
     */
    public function testCustomerPlaceOrderWithDifferentProductTypesCustomWebsite(): void
    {
        $this->_markTestAsRestOnly();
        $this->assignCustomerToCustomWebsite();
        $this->assignProductsToCustomWebsite();
        $this->storeViewCode = 'store_for_eu_website';
        $customerTokenService = $this->objectManager->create(CustomerTokenServiceInterface::class);
        $this->customerToken = $customerTokenService->createCustomerAccessToken('customer@example.com', 'password');
        $this->cartId = $this->getCart();
        $this->addProducts();
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
    }

    /**
     * Create order with different types of products - guest customer, single stock mode, default website.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_simple.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryBundleProduct/Test/_files/product_bundle.php
     *
     * @return void
     */
    public function testGuestPlaceOrderWithDifferentProductTypesDefaultWebsite(): void
    {
        $this->_markTestAsRestOnly();
        $this->cartId = $this->getGuestCart();
        $this->addProducts();
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
    }

    /**
     * Create order with different types of products - guest customer, single stock mode, default website.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_simple.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_virtual.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/product_downloadable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryConfigurableProduct/Test/_files/product_configurable.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryBundleProduct/Test/_files/product_bundle.php
     *
     * @return void
     */
    public function testGuestPlaceOrderWithDifferentProductTypesCustomWebsite(): void
    {
        $this->_markTestAsRestOnly();
        $this->assignProductsToCustomWebsite();
        $this->storeViewCode = 'store_for_eu_website';
        $this->cartId = $this->getGuestCart();
        $this->addProducts();
        $this->estimateShippingCosts();
        $this->setShippingAndBillingInformation();
        $orderId = $this->submitPaymentInformation();
        $this->verifyCreatedOrder($orderId);
    }

    /**
     * Get registered customer empty cart.
     *
     * @return int
     */
    private function getCart(): int
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/carts/mine',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $this->customerToken
            ],
        ];

        return $this->_webApiCall($serviceInfo, [], null, $this->storeViewCode);
    }

    /**
     * Get guest customer empty cart.
     *
     * @return string
     */
    private function getGuestCart(): string
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/guest-carts/',
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
        ];
        return $this->_webApiCall($serviceInfo, [], null, $this->storeViewCode);
    }

    /**
     * Add products to cart.
     *
     * @return void
     */
    private function addProducts(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/guest-carts/' . $this->cartId . '/items',
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],

        ];
        if ($this->customerToken) {
            $serviceInfo = [
                'rest' => [
                    'resourcePath' => '/V1/carts/mine/items',
                    'httpMethod' => Request::HTTP_METHOD_POST,
                    'token' => $this->customerToken
                ],

            ];
        }

        $this->addSimpleProduct($serviceInfo);
        $this->addVirtualProduct($serviceInfo);
        $this->addDownloadableProduct($serviceInfo);
        $this->addConfigurableProduct($serviceInfo);
        $this->addBundleProduct($serviceInfo);
    }

    /**
     * Estimate shipping costs for given customer cart.
     *
     * @return void
     */
    private function estimateShippingCosts(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/guest-carts/' . $this->cartId . '/estimate-shipping-methods',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $this->customerToken
            ],
        ];

        if ($this->customerToken) {
            $serviceInfo = [
                'rest' => [
                    'resourcePath' => '/V1/carts/mine/estimate-shipping-methods',
                    'httpMethod' => Request::HTTP_METHOD_POST,
                    'token' => $this->customerToken
                ],
            ];
        }

        $body = [
            'address' => [
                'region' => 'California',
                'region_id' => 12,
                'region_code' => 'CA',
                'country_id' => 'US',
                'street' => ['6161 West Centinela Avenue'],
                'postcode' => '90230',
                'city' => 'Culver City',
                'firstname' => 'John',
                'lastname' => 'Smith',
                'customer_id' => 1,
                'email' => 'customer@example.com',
                'telephone' => '(555) 555-5555',
                'same_as_billing' => 1,
            ]
        ];
        $this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * Submit payment information for given customer cart.
     *
     * @return int
     */
    private function submitPaymentInformation(): int
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/guest-carts/' . $this->cartId . '/payment-information',
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
        ];
        if ($this->customerToken) {
            $serviceInfo = [
                'rest' => [
                    'resourcePath' => '/V1/carts/mine/payment-information',
                    'httpMethod' => Request::HTTP_METHOD_POST,
                    'token' => $this->customerToken
                ],
            ];
        }

        $body = [
            'email' => 'customer@example.com',
            'paymentMethod' => ['method' => 'checkmo'],
            'billing_address' => [
                'email' => 'customer@example.com',
                'region' => 'California',
                'region_id' => 12,
                'region_code' => 'CA',
                'country_id' => 'US',
                'street' => ['6161 West Centinela Avenue'],
                'postcode' => '90230',
                'city' => 'Culver City',
                'telephone' => '(555) 555-5555',
                'firstname' => 'John',
                'lastname' => 'Smith'
            ]
        ];

        return (int)$this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * Set shipping and billing information for given customer cart.
     *
     * @return void
     */
    private function setShippingAndBillingInformation(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/guest-carts/' . $this->cartId . '/shipping-information',
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
        ];
        if ($this->customerToken) {
            $serviceInfo = [
                'rest' => [
                    'resourcePath' => '/V1/carts/mine/shipping-information',
                    'httpMethod' => Request::HTTP_METHOD_POST,
                    'token' => $this->customerToken
                ],
            ];
        }

        $body = [
            'addressInformation' => [
                    'shipping_address' => [
                            'region' => 'California',
                            'region_id' => 12,
                            'region_code' => 'CA',
                            'country_id' => 'US',
                            'street' => [
                                    0 => '6161 West Centinela Avenue',
                                ],
                            'postcode' => '90230',
                            'city' => 'Culver City',
                            'firstname' => 'John',
                            'lastname' => 'Smith',
                            'email' => 'customer@example.com',
                            'telephone' => '(555) 555-5555',
                        ],
                    'billing_address' => [
                            'region' => 'California',
                            'region_id' => 12,
                            'region_code' => 'CA',
                            'country_id' => 'US',
                            'street' => [
                                    0 => '6161 West Centinela Avenue',
                                ],
                            'postcode' => '90230',
                            'city' => 'Culver City',
                            'firstname' => 'John',
                            'lastname' => 'Smith',
                            'email' => 'customer@example.com',
                            'telephone' => '(555) 555-5555',
                        ],
                    'shipping_carrier_code' => 'flatrate',
                    'shipping_method_code' => 'flatrate',
                ],
        ];
        $this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * Verify, created order is correct.
     *
     * @param int $orderId
     * @return void
     */
    private function verifyCreatedOrder(int $orderId): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/orders/' . $orderId,
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
        ];
        $order = $this->_webApiCall($serviceInfo, [], null, $this->storeViewCode);
        $this->assertGreaterThan(0, $order['increment_id']);
        $this->assertEquals('customer@example.com', $order['customer_email']);

        $this->assertEquals('simple-product', $order['items'][0]['sku']);
        $this->assertEquals('simple', $order['items'][0]['product_type']);
        $this->assertEquals(10, $order['items'][0]['price']);
        $this->assertEquals(1, $order['items'][0]['qty_ordered']);

        $this->assertEquals('virtual-product', $order['items'][1]['sku']);
        $this->assertEquals('virtual', $order['items'][1]['product_type']);
        $this->assertEquals(10, $order['items'][1]['price']);
        $this->assertEquals(1, $order['items'][1]['qty_ordered']);

        $this->assertEquals('downloadable-product', $order['items'][2]['sku']);
        $this->assertEquals('downloadable', $order['items'][2]['product_type']);
        $this->assertEquals(10, $order['items'][2]['price']);
        $this->assertEquals(1, $order['items'][2]['qty_ordered']);

        $this->assertEquals('simple_10', $order['items'][3]['sku']);
        $this->assertEquals('configurable', $order['items'][3]['product_type']);
        $this->assertEquals(10, $order['items'][3]['price']);
        $this->assertEquals(1, $order['items'][3]['qty_ordered']);

        $this->assertEquals('bundle-simple_product_bundle_option', $order['items'][5]['sku']);
        $this->assertEquals('bundle', $order['items'][5]['product_type']);
        $this->assertEquals(10, $order['items'][5]['price']);
        $this->assertEquals(1, $order['items'][5]['qty_ordered']);
    }

    /**
     * Add simple product to cart.
     *
     * @param array $serviceInfo
     * @return void
     */
    private function addSimpleProduct(array $serviceInfo): void
    {
        $simpleProduct = [
            'cartItem' => [
                    'sku' => 'simple-product',
                    'qty' => 1,
                    'quote_id' => $this->cartId,
                ],
        ];
        $this->_webApiCall($serviceInfo, $simpleProduct, null, $this->storeViewCode);
    }

    /**
     * Add virtual product to cart.
     *
     * @param array $serviceInfo
     * @return void
     */
    private function addVirtualProduct(array $serviceInfo): void
    {
        $virtualProduct = [
            'cartItem' => [
                    'sku' => 'virtual-product',
                    'qty' => 1,
                    'quote_id' => $this->cartId,
                ]
        ];
        $this->_webApiCall($serviceInfo, $virtualProduct, null, $this->storeViewCode);
    }

    /**
     * Add downloadable product to cart.
     *
     * @param array $serviceInfo
     * @return void
     */
    private function addDownloadableProduct(array $serviceInfo): void
    {
        $downloadableProduct = [
            'cartItem' => [
                    'sku' => 'downloadable-product',
                    'qty' => 1,
                    'quote_id' => $this->cartId,
                ]
        ];
        $this->_webApiCall($serviceInfo, $downloadableProduct, null, $this->storeViewCode);
    }

    /**
     * Add configurable product to cart.
     *
     * @param array $serviceInfo
     * @return void
     */
    private function addConfigurableProduct(array $serviceInfo): void
    {
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $product = $productRepository->get('configurable', false, $this->storeViewCode);
        $configurableProductOptions = $product->getExtensionAttributes()->getConfigurableProductOptions();
        $attributeId = $configurableProductOptions[0]->getAttributeId();
        $options = $configurableProductOptions[0]->getOptions();
        $optionId = $options[0]['value_index'];

        $configurableProduct = [
            'cartItem' => [
                    'sku' => 'configurable',
                    'qty' => 1,
                    'quote_id' => $this->cartId,
                    'product_option' => [
                        'extension_attributes' => [
                            'configurable_item_options' => [
                                [
                                    'option_id' => $attributeId,
                                    'option_value' => $optionId,
                                ]
                            ]
                        ]
                    ]
                ]
        ];
        $this->_webApiCall($serviceInfo, $configurableProduct, null, $this->storeViewCode);
    }

    /**
     * Add bundle product to cart.
     *
     * @param array $serviceInfo
     * @return void
     */
    private function addBundleProduct(array $serviceInfo): void
    {
        $productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $product = $productRepository->get('bundle', false, $this->storeViewCode);
        $bundleProductOption = $product->getExtensionAttributes()->getBundleProductOptions()[0];
        $bundleProduct = [
            'cartItem' => [
                'sku' => 'bundle',
                'qty' => 1,
                'quote_id' => $this->cartId,
                'product_option' => [
                    'extension_attributes' => [
                        'bundle_options' => [
                            [
                                'option_id' => $bundleProductOption->getId(),
                                'option_qty' => 2,
                                'option_selections' => [0 => $bundleProductOption->getId()]
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->_webApiCall($serviceInfo, $bundleProduct, null, $this->storeViewCode);
    }

    /**
     * Assign test products to custom website.
     *
     * @return void
     */
    private function assignProductsToCustomWebsite(): void
    {
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $websiteId = $websiteRepository->get('eu_website')->getId();
        $searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder->addFilter(
            ProductInterface::SKU,
            [
                'simple-product',
                'downloadable-product',
                'virtual-product',
                'configurable',
                'simple_10',
                'bundle',
                'simple_product_bundle_option',
            ],
            'in'
        )->create();
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $products = $productRepository->getList($searchCriteria)->getItems();

        foreach ($products as $product) {
            $product->setWebsiteIds([$websiteId]);
            $productRepository->save($product);
        }
    }

    /**
     * Assign test customer to custom website.
     *
     * @return void
     */
    private function assignCustomerToCustomWebsite(): void
    {
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $websiteId = $websiteRepository->get('eu_website')->getId();
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->get('customer@example.com');
        $customer->setWebsiteId($websiteId);
        $customerRepository->save($customer);
    }
}

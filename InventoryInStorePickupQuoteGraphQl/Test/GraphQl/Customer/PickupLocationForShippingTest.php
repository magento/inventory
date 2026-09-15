<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuoteGraphQl\Test\GraphQl\Customer;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventorySales\Model\SalesChannel;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test coverage of extension of Quote Graph Ql.
 * Test possibility to pass Pickup Location Code to Shipping Address.
 */
class PickupLocationForShippingTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->getPickupLocation = $objectManager->get(GetPickupLocationInterface::class);
        $this->storeManager = $objectManager->get(StoreManagerInterface::class)->getStore();
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Customer/_files/customer_two_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/create_in_store_pickup_quote_on_eu_website_customer.php
     *
     * @magentoConfigFixture store_for_eu_website_store customer/account_share/scope 0
     *
     * @throws AuthenticationException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Exception
     */
    public function testSetPickupLocationForShippingAddressFromAddressBook()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('in_store_pickup_test_order');
        $pickupLocationCode = 'eu-1';

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          customer_address_id: 1,
          pickup_location_code: "$pickupLocationCode"
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
        country {
          label
          code
        },
        pickup_location_code
      }
    }
  }
}
QUERY;
        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            array_merge($this->getAuthHeader(), $this->getStoreHeader())
        );

        self::assertArrayHasKey('cart', $response['setShippingAddressesOnCart']);
        $cartResponse = $response['setShippingAddressesOnCart']['cart'];
        self::assertArrayHasKey('shipping_addresses', $cartResponse);
        $shippingAddressResponse = current($cartResponse['shipping_addresses']);
        $this->assertShippingAddressFields($shippingAddressResponse, $pickupLocationCode);
    }

    /**
     * Assert shipping address fields from the response.
     *
     * @param array $shippingAddressResponse
     * @param string $pickupLocationCode
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function assertShippingAddressFields(array $shippingAddressResponse, string $pickupLocationCode)
    {
        $pickupLocation = $this->getPickupLocation->execute(
            $pickupLocationCode,
            SalesChannel::TYPE_WEBSITE,
            $this->storeManager->getWebsite()->getCode()
        );

        $assertionMap = [
            ['response_field' => 'firstname', 'expected_value' => 'John'],
            ['response_field' => 'lastname', 'expected_value' => 'Smith'],
            ['response_field' => 'company', 'expected_value' => 'CompanyName'],
            ['response_field' => 'street', 'expected_value' => [0 => $pickupLocation->getStreet()]],
            ['response_field' => 'city', 'expected_value' => $pickupLocation->getCity()],
            ['response_field' => 'postcode', 'expected_value' => $pickupLocation->getPostcode()],
            ['response_field' => 'telephone', 'expected_value' => '3468676'],
            [
                'response_field' => 'country',
                'expected_value' => [
                    'code' => $pickupLocation->getCountryId(),
                    'label' => $pickupLocation->getCountryId()
                ]
            ],
            ['response_field' => 'pickup_location_code', 'expected_value' => $pickupLocation->getPickupLocationCode()]
        ];

        $this->assertResponseFields($shippingAddressResponse, $assertionMap);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/create_in_store_pickup_quote_on_eu_website_customer.php
     *
     * @magentoConfigFixture store_for_eu_website_store customer/account_share/scope 0
     *
     * @throws AuthenticationException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Exception
     */
    public function testSetPickupLocationForNewShippingAddress()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('in_store_pickup_test_order');
        $pickupLocationCode = 'eu-1';

        $query = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          address: {
            firstname: "test firstname"
            lastname: "test lastname"
            company: "test company"
            street: ["test street 1", "test street 2"]
            city: "test city"
            region: "test region"
            postcode: "887766"
            country_code: "US"
            telephone: "88776655"
            save_in_address_book: false
          },
          pickup_location_code: "$pickupLocationCode"
        }
      ]
    }
  ) {
    cart {
      shipping_addresses {
        firstname
        lastname
        company
        street
        city
        postcode
        telephone
        country {
          label
          code
        },
        pickup_location_code,
        __typename
      }
    }
  }
}
QUERY;
        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            array_merge($this->getAuthHeader(), $this->getStoreHeader())
        );

        self::assertArrayHasKey('cart', $response['setShippingAddressesOnCart']);
        $cartResponse = $response['setShippingAddressesOnCart']['cart'];
        self::assertArrayHasKey('shipping_addresses', $cartResponse);
        $shippingAddressResponse = current($cartResponse['shipping_addresses']);
        $this->assertNewShippingAddressFields($shippingAddressResponse, $pickupLocationCode);
    }

    /**
     * Assert fields for new shipping address.
     *
     * @param array $shippingAddressResponse
     * @param string $pickupLocationCode
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function assertNewShippingAddressFields(array $shippingAddressResponse, string $pickupLocationCode)
    {
        $pickupLocation = $this->getPickupLocation->execute(
            $pickupLocationCode,
            SalesChannel::TYPE_WEBSITE,
            $this->storeManager->getWebsite()->getCode()
        );

        $assertionMap = [
            ['response_field' => 'firstname', 'expected_value' => 'test firstname'],
            ['response_field' => 'lastname', 'expected_value' => 'test lastname'],
            ['response_field' => 'company', 'expected_value' => 'test company'],
            ['response_field' => 'street', 'expected_value' => [0 => $pickupLocation->getStreet()]],
            ['response_field' => 'city', 'expected_value' => $pickupLocation->getCity()],
            ['response_field' => 'postcode', 'expected_value' => $pickupLocation->getPostcode()],
            ['response_field' => 'telephone', 'expected_value' => '88776655'],
            [
                'response_field' => 'country',
                'expected_value' => [
                    'code' => $pickupLocation->getCountryId(),
                    'label' => $pickupLocation->getCountryId()
                ]
            ],
            ['response_field' => 'pickup_location_code', 'expected_value' => $pickupLocation->getPickupLocationCode()],
            ['response_field' => '__typename', 'expected_value' => 'ShippingCartAddress']
        ];

        $this->assertResponseFields($shippingAddressResponse, $assertionMap);
    }

    /**
     * A shipping method selected before switching the address to a Pickup Location must not be
     * silently retained on the resulting Pickup Location order.
     *
     * Reproduces the reported scenario: set a regular shipping address and a non-pickup shipping method
     * (flat rate), then change the shipping address to a Pickup Location using `pickup_location_code`. The
     * previously selected flat rate method must be cleared instead of staying attached to the pickup order.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/assign_products_to_websites.php
     *
     * @magentoConfigFixture store_for_eu_website_store customer/account_share/scope 0
     *
     * @throws AuthenticationException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws \Exception
     */
    public function testNonPickupShippingMethodIsClearedWhenAddressSwitchedToPickupLocation(): void
    {
        $pickupLocationCode = 'eu-1';
        $headers = array_merge($this->getAuthHeader(), $this->getStoreHeader());

        // The `eu-1` source fixture does not set a phone number, but a shipping address built purely from
        // `pickup_location_code` (no other address fields, matching the ticket's exact repro) requires one.
        $this->setPickupSourcePhone($pickupLocationCode, '3468676');

        $maskedQuoteId = $this->getCustomerCartId($headers);
        $this->addItemToCart($maskedQuoteId, $headers);
        $this->setRegularShippingAddress($maskedQuoteId, $headers);
        $this->assertFlatRateShippingMethodIsSelected($maskedQuoteId, $headers);

        $shippingAddress = $this->switchShippingAddressToPickupLocation($maskedQuoteId, $pickupLocationCode, $headers);

        self::assertEquals($pickupLocationCode, $shippingAddress['pickup_location_code']);
        self::assertNull(
            $shippingAddress['selected_shipping_method'],
            'The previously selected non-pickup shipping method must not be retained on a Pickup Location order.'
        );
    }

    /**
     * Set a phone number on the given Pickup Location source, required to build a pickup-only address.
     *
     * @param string $pickupLocationCode
     * @param string $phone
     *
     * @return void
     */
    private function setPickupSourcePhone(string $pickupLocationCode, string $phone): void
    {
        $sourceRepository = Bootstrap::getObjectManager()->get(SourceRepositoryInterface::class);
        $pickupSource = $sourceRepository->get($pickupLocationCode);
        $pickupSource->setPhone($phone);
        $sourceRepository->save($pickupSource);
    }

    /**
     * Get the masked id of the current customer's cart.
     *
     * @param array $headers
     *
     * @return string
     */
    private function getCustomerCartId(array $headers): string
    {
        $cartQuery = <<<QUERY
{
  customerCart {
    id
  }
}
QUERY;
        $cartResponse = $this->graphQlQuery($cartQuery, [], '', $headers);

        return $cartResponse['customerCart']['id'];
    }

    /**
     * Add a simple product to the cart.
     *
     * @param string $maskedQuoteId
     * @param array $headers
     *
     * @return void
     */
    private function addItemToCart(string $maskedQuoteId, array $headers): void
    {
        $addItemQuery = <<<QUERY
mutation {
  addSimpleProductsToCart(
    input: {
      cart_id: "$maskedQuoteId"
      cart_items: [{ data: { quantity: 1, sku: "SKU-1" } }]
    }
  ) {
    cart {
      id
    }
  }
}
QUERY;
        $this->graphQlMutation($addItemQuery, [], '', $headers);
    }

    /**
     * Set a regular, non-pickup shipping address on the cart.
     *
     * @param string $maskedQuoteId
     * @param array $headers
     *
     * @return void
     */
    private function setRegularShippingAddress(string $maskedQuoteId, array $headers): void
    {
        $setAddressQuery = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [
        {
          address: {
            firstname: "Bob"
            lastname: "Roll"
            street: ["Magento Pkwy"]
            city: "Culver City"
            region_id: 12
            postcode: "90230"
            country_code: "US"
            telephone: "8675309"
            save_in_address_book: false
          }
        }
      ]
    }
  ) {
    cart {
      id
    }
  }
}
QUERY;
        $this->graphQlMutation($setAddressQuery, [], '', $headers);
    }

    /**
     * Set a non-pickup (flat rate) shipping method on the cart and assert it was applied.
     *
     * @param string $maskedQuoteId
     * @param array $headers
     *
     * @return void
     */
    private function assertFlatRateShippingMethodIsSelected(string $maskedQuoteId, array $headers): void
    {
        $setMethodQuery = <<<QUERY
mutation {
  setShippingMethodsOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_methods: [{ carrier_code: "flatrate", method_code: "flatrate" }]
    }
  ) {
    cart {
      shipping_addresses {
        selected_shipping_method {
          carrier_code
          method_code
        }
      }
    }
  }
}
QUERY;
        $methodResponse = $this->graphQlMutation($setMethodQuery, [], '', $headers);
        $selectedMethod = current(
            $methodResponse['setShippingMethodsOnCart']['cart']['shipping_addresses']
        )['selected_shipping_method'];
        self::assertEquals('flatrate', $selectedMethod['carrier_code']);
        self::assertEquals('flatrate', $selectedMethod['method_code']);
    }

    /**
     * Switch the cart's shipping address to a Pickup Location and return the resulting shipping address.
     *
     * @param string $maskedQuoteId
     * @param string $pickupLocationCode
     * @param array $headers
     *
     * @return array
     */
    private function switchShippingAddressToPickupLocation(
        string $maskedQuoteId,
        string $pickupLocationCode,
        array $headers
    ): array {
        $setPickupAddressQuery = <<<QUERY
mutation {
  setShippingAddressesOnCart(
    input: {
      cart_id: "$maskedQuoteId"
      shipping_addresses: [{ pickup_location_code: "$pickupLocationCode" }]
    }
  ) {
    cart {
      shipping_addresses {
        pickup_location_code
        selected_shipping_method {
          carrier_code
          method_code
        }
      }
    }
  }
}
QUERY;
        $response = $this->graphQlMutation($setPickupAddressQuery, [], '', $headers);

        return current($response['setShippingAddressesOnCart']['cart']['shipping_addresses']);
    }

    /**
     * Get header with information about the source.
     *
     * @return array
     */
    private function getStoreHeader(): array
    {
        return ['Store' => 'store_for_eu_website'];
    }

    /**
     * Get header for authorization.
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     * @throws AuthenticationException
     */
    private function getAuthHeader(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $authHeader = ['Authorization' => 'Bearer ' . $customerToken];

        return $authHeader;
    }
}

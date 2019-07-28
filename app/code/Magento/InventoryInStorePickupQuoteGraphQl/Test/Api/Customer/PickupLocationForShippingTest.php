<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuoteGraphQl\Test\Api\Customer;

use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationInterface;
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

    public function setUp()
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
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/create_in_store_pickup_quote_on_eu_website_customer.php
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
        }
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
        ];

        $this->assertResponseFields($shippingAddressResponse, $assertionMap);
    }

    /**
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items_eu_stock_only.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/create_in_store_pickup_quote_on_eu_website_customer.php
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
        }
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
            ['response_field' => '__typename', 'expected_value' => 'ShippingCartAddress']
        ];

        $this->assertResponseFields($shippingAddressResponse, $assertionMap);
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

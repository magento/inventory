<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesApi\Test\GraphQl;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Registry;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Verify order placement with 'in store pickup' delivery method.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrderTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var CustomerTokenServiceInterface
     */
    private $customerTokenService;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var OrderInterface
     */
    private $order;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->orderRepository = $objectManager->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->sourceRepository = $objectManager->get(SourceRepositoryInterface::class);
        $this->customerTokenService = $objectManager->get(CustomerTokenServiceInterface::class);
        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
    }

    /**
     * Tear down.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        /** @var Registry $registry */
        $registry = Bootstrap::getObjectManager()->get(Registry::class);
        $orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);

        $orderManagement->cancel($this->order->getEntityId());
        $this->orderRepository->delete($this->order);

        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', false);
        $this->order = null;
    }

    /**
     * Verify ability to place order with 'in store pickup' delivery method for guest customer.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/store_for_eu_website_store_carriers_conf.php
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
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/create_in_store_pickup_quote_on_eu_website_guest.php
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPlaceOrderWithStorePickupDeliveryMethodGuestCustomer(): void
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('in_store_pickup_test_order');
        $query = <<<QUERY
mutation {
  placeOrder(input: {cart_id: "$maskedQuoteId"}) {
    order {
      order_number
    }
  }
}

QUERY;
        $response = $this->graphQlMutation(
            $query,
            [],
            '',
            $this->getStoreHeader()
        );

        $this->verifyOrder($response);
    }

    /**
     * Verify ability to place order with 'in store pickup' delivery method for registered customer with addresses.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Customer/_files/customer_two_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/store_for_eu_website_store_carriers_conf.php
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
     * @magentoConfigFixture store_for_eu_website_store customer/account_share/scope 0
     *
     * @return void
     * @throws \Magento\Framework\Exception\AuthenticationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPlaceOrderWithStorePickupDeliveryMethodRegisteredCustomerExistedAddress(): void
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('in_store_pickup_test_order');
        $query = <<<QUERY
mutation {
  placeOrder(input: {cart_id: "$maskedQuoteId"}) {
    order {
      order_number
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

        $this->verifyOrder($response);
    }

    /**
     * Verify ability to place order with 'in store pickup' delivery method for registered customer without address.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/store_for_eu_website_store_carriers_conf.php
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
     * @magentoConfigFixture store_for_eu_website_store customer/account_share/scope 0
     *
     * @return void
     * @throws \Magento\Framework\Exception\AuthenticationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPlaceOrderWithStorePickupDeliveryMethodRegisteredCustomerNewAddress(): void
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('in_store_pickup_test_order');
        $query = <<<QUERY
mutation {
  placeOrder(input: {cart_id: "$maskedQuoteId"}) {
    order {
      order_number
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

        $this->verifyOrder($response);
    }

    /**
     * Verify ability to place order with 'in store pickup' delivery method for registered customer with
     *
     * 'save in address book' shipping address.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/store_for_eu_website_store_carriers_conf.php
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
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/quote_with_save_shipping_address_to_address_book.php
     * @magentoConfigFixture store_for_eu_website_store customer/account_share/scope 0
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPlaceOrderWithStorePickupDeliveryMethodRegisteredCustomerAddressSaveInBook(): void
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('in_store_pickup_test_order');
        $query = <<<QUERY
mutation {
  placeOrder(input: {cart_id: "$maskedQuoteId"}) {
    order {
      order_number
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

        $this->verifyOrder($response);
        $customer = $this->customerRepository->get('customer@example.com');
        $addresses = $customer->getAddresses();
        self::assertEmpty($addresses);
    }

    /**
     * Verify ability to place order with 'in store pickup' delivery method for registered customer with
     *
     * shipping address 'same as billing'.
     *
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/Customer/_files/customer_two_addresses.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/store_for_eu_website_store_carriers_conf.php
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
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryInStorePickupSalesApi/Test/_files/quote_with_shipping_address_same_as_billing.php
     * @magentoConfigFixture store_for_eu_website_store customer/account_share/scope 0
     *
     * @return void
     * @throws \Magento\Framework\Exception\AuthenticationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testPlaceOrderWithStorePickupDeliveryMethodRegisteredCustomerSameAsBillingAddress(): void
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('in_store_pickup_test_order');
        $query = <<<QUERY
mutation {
  placeOrder(input: {cart_id: "$maskedQuoteId"}) {
    order {
      order_number
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

        $this->verifyOrder($response);
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
     * @throws \Magento\Framework\Exception\AuthenticationException
     */
    private function getAuthHeader(string $username = 'customer@example.com', string $password = 'password'): array
    {
        $customerToken = $this->customerTokenService->createCustomerAccessToken($username, $password);
        $authHeader = ['Authorization' => 'Bearer ' . $customerToken];

        return $authHeader;
    }

    /**
     * Verify created order.
     *
     * @param array $response
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function verifyOrder(array $response): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $response['placeOrder']['order']['order_number'])
            ->create();
        $this->order = current($this->orderRepository->getList($searchCriteria)->getItems());
        $source = $this->sourceRepository->get('eu-1');
        $address = current($this->order->getExtensionAttributes()->getShippingAssignments())
            ->getShipping()
            ->getAddress();
        self::assertEquals('instore_pickup', $this->order->getShippingMethod());
        self::assertEquals('John', $address->getFirstName());
        self::assertEquals('Doe', $address->getLastName());
        self::assertEquals('customer@example.com', $address->getEmail());
        self::assertEquals($source->getSourceCode(), $this->order->getExtensionAttributes()->getPickupLocationCode());
        self::assertEquals($source->getRegion(), $address->getRegion());
        self::assertEquals($source->getPostcode(), $address->getPostCode());
        self::assertEquals($source->getStreet(), current($address->getStreet()));
        self::assertEquals($source->getCity(), $address->getCity());
        self::assertEquals($source->getCountryId(), $address->getCountryId());
    }
}

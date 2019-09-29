<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Api;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\Customer\Api\Data\CustomerInterface;

/**
 * Base class for order placement.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class OrderPlacementBase extends WebapiAbstract
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Registered customer token.
     *
     * @var string
     */
    protected $customerToken;

    /**
     * Registered or guest customer cart id.
     *
     * @var string
     */
    protected $cartId;

    /**
     * Store code to make request to specific website.
     *
     * @var string
     */
    protected $storeViewCode = 'default';

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * Set store view for test.
     *
     * @param $storeViewCode
     */
    public function setStoreView($storeViewCode)
    {
        $this->storeViewCode = $storeViewCode;
    }

    /**
     * Retrieve registered customer token.
     *
     * @param string $customerEmail
     * @param string $customerPassword
     * @return string
     */
    public function getCustomerToken(string $customerEmail, string $customerPassword): string
    {
        if (!$this->customerToken) {
            $customerTokenService = $this->objectManager->create(CustomerTokenServiceInterface::class);
            $this->customerToken = $customerTokenService->createCustomerAccessToken($customerEmail, $customerPassword);
        }

        return $this->customerToken;
    }

    /**
     * Assign customer to additional website.
     *
     * @param string $customerEmail
     * @param string $websiteCode
     * @return void
     */
    public function assignCustomerToCustomWebsite(string $customerEmail, string $websiteCode): void
    {
        $websiteRepository = $this->objectManager->get(WebsiteRepositoryInterface::class);
        $websiteId = $websiteRepository->get($websiteCode)->getId();
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->get($customerEmail);
        $customer->setWebsiteId($websiteId);
        $customerRepository->save($customer);
    }

    /**
     * Get customer empty cart.
     *
     * @return void
     */
    public function createCustomerCart(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/guest-carts/',
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
        ];

        if ($this->customerToken) {
            $serviceInfo = [
                'rest' => [
                    'resourcePath' => '/V1/carts/mine',
                    'httpMethod' => Request::HTTP_METHOD_POST,
                    'token' => $this->customerToken
                ],
            ];

        }

        $this->cartId = (string)$this->_webApiCall($serviceInfo, [], null, $this->storeViewCode);
    }

    /**
     * Add simple, virtual or downloadable product to cart.
     *
     * @param string $sku
     * @param int $qty
     * @return void
     */
    public function addProduct(string $sku, $qty = 1): void
    {
        $serviceInfo = $this->getAddProductServiceInfo();

        $product = [
            'cartItem' => [
                'sku' => $sku,
                'qty' => $qty,
                'quote_id' => $this->cartId,
            ],
        ];
        $this->_webApiCall($serviceInfo, $product, null, $this->storeViewCode);
    }

    /**
     * Get service info for add product to cart.
     *
     * @return array
     */
    protected function getAddProductServiceInfo(): array
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

        return $serviceInfo;
    }

    /**
     * Estimate shipping costs for given customer cart.
     * @return void
     */
    public function estimateShippingCosts(): void
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
            'address' => $this->getBaseAddressData()
        ];

        $this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * Estimate shipping costs for given customer cart by addressId.
     * @return void
     */
    public function estimateShippingCostsByAddressId($addressId): void
    {
        if (!$this->customerToken) {
            return;
        }

        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/carts/mine/estimate-shipping-methods-by-address-id',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $this->customerToken
            ],
        ];

        $body = [
            'addressId' => $addressId
        ];

        $this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * Set shipping and billing information for given customer cart.
     *
     * @param string|null $addressId
     * @param bool $saveInAddressBook
     *
     * @return void
     */
    public function setShippingAndBillingInformation($addressId = null, $saveInAddressBook = false): void
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

        $addressData = $this->getBaseAddressData();
        if ($addressId) {
            $addressData['id'] = $addressId;
        }

        if ($saveInAddressBook) {
            $addressData['saveInAddressBook'] = 1;
        }

        $body = [
            'addressInformation' => [
                'shipping_address' => array_merge($addressData, [
                    'extension_attributes' => ['pickup_location_code' => 'eu-1']
                ]),
                'billing_address' => $addressData,
                'shipping_carrier_code' => 'flatrate',
                'shipping_method_code' => 'flatrate'
            ],
        ];
        $this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * Submit payment information for given customer cart.
     *
     * @param bool $billingSameAsShipping
     *
     * @return int
     */
    public function submitPaymentInformation($billingSameAsShipping = false): int
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
            'paymentMethod' => ['method' => 'checkmo']
        ];

        if (!$billingSameAsShipping) {
            $body['billing_address'] = $this->getBaseAddressData();
        }

        return (int)$this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * Retrieve order by id.
     *
     * @param int $orderId
     * @return array
     */
    public function getOrder(int $orderId): array
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/orders/' . $orderId,
                'httpMethod' => Request::HTTP_METHOD_GET,
            ],
        ];
        return $this->_webApiCall($serviceInfo, [], null, $this->storeViewCode);
    }

    /**
     * Cancel order by id.
     *
     * @param int $orderId
     * @return void
     */
    public function cancelOrder(int $orderId): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/orders/' . $orderId . '/cancel',
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
        ];
        $this->_webApiCall($serviceInfo, [], null, $this->storeViewCode);
    }

    /**
     * @param int $customerId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function assignAddressToTheCustomer(int $customerId): void
    {
        $shippingAddressData = $this->getBaseAddressData();

        /** @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepo */
        $addressRepo = $this->objectManager->get(\Magento\Customer\Api\AddressRepositoryInterface::class);
        /** @var \Magento\Customer\Api\Data\AddressInterface $address */
        $address = $this->objectManager->create(\Magento\Customer\Api\Data\AddressInterface::class);
        /** @var \Magento\Customer\Api\Data\RegionInterface $region */
        $region = $this->objectManager->create(\Magento\Customer\Api\Data\RegionInterface::class);
        $region->setRegion($shippingAddressData['region'])
            ->setRegionId($shippingAddressData['region_id'])
            ->setRegionCode($shippingAddressData['region_code']);
        $address->setCity($shippingAddressData['city'])
            ->setRegion($region)
            ->setCountryId($shippingAddressData['country_id'])
            ->setStreet($shippingAddressData['street'])
            ->setPostcode($shippingAddressData['postcode'])
            ->setFirstname($shippingAddressData['firstname'])
            ->setLastname($shippingAddressData['lastname'])
            ->setTelephone($shippingAddressData['telephone'])
            ->setCustomerId($customerId)
            ->setIsDefaultBilling(true)
            ->setIsDefaultShipping(true);

        $addressRepo->save($address);
    }

    /**
     * Get address data for cart build.
     *
     * @return array
     */
    protected function getBaseAddressData()
    {
        return [
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
        ];
    }

    /**
     * @param string $customerEmail
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerByEmail(string $customerEmail): CustomerInterface
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        /** @var CustomerInterface $customer */
        $customer = $customerRepository->get($customerEmail);

        /** @var \Magento\Customer\Model\CustomerRegistry $customerRegistry */
        $customerRegistry = $this->objectManager->get(\Magento\Customer\Model\CustomerRegistry::class);
        $customerRegistry->remove($customer->getId());

        $customer = $customerRepository->get($customerEmail);

        return $customer;
    }

    /**
     * Get fresh address list;
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerAddressList(): array
    {
        $customer = $this->getCustomerByEmail('customer@example.com');

        /** @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepo */
        $addressRepo = $this->objectManager->get(\Magento\Customer\Api\AddressRepositoryInterface::class);
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        /** @var \Magento\Framework\Api\FilterBuilder $filterBuilder */
        $filterBuilder = $this->objectManager->get(\Magento\Framework\Api\FilterBuilder::class);

        $filter =  $filterBuilder->setField('parent_id')
            ->setValue($customer->getId())
            ->setConditionType('eq')
            ->create();
        $addresses = (array)($addressRepo->getList(
            $searchCriteriaBuilder->addFilters([$filter])->create()
        )->getItems());

        return $addresses;
    }
}

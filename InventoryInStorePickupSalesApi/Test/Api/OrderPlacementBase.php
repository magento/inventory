<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesApi\Test\Api;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventorySales\Test\Api\OrderPlacementBase as OrderPlacementBaseSales;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Base class for order placement.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class OrderPlacementBase extends OrderPlacementBaseSales
{
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
                'token' => $this->customerToken,
            ],
        ];

        if ($this->customerToken) {
            $serviceInfo = [
                'rest' => [
                    'resourcePath' => '/V1/carts/mine/estimate-shipping-methods',
                    'httpMethod' => Request::HTTP_METHOD_POST,
                    'token' => $this->customerToken,
                ],
            ];
        }

        $body = [
            'address' => $this->getBaseAddressData(),
        ];

        $this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * Estimate shipping costs for given customer cart by addressId.
     *
     * @return void
     * @var string $addressId
     *
     */
    public function estimateShippingCostsByAddressId(string $addressId): void
    {
        if (!$this->customerToken) {
            return;
        }

        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/carts/mine/estimate-shipping-methods-by-address-id',
                'httpMethod' => Request::HTTP_METHOD_POST,
                'token' => $this->customerToken,
            ],
        ];

        $body = [
            'addressId' => $addressId,
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
                    'token' => $this->customerToken,
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
                'shipping_address' => array_merge(
                    $addressData,
                    ['extension_attributes' => ['pickup_location_code' => 'eu-1']]
                ),
                'billing_address' => $addressData,
                'shipping_carrier_code' => 'flatrate',
                'shipping_method_code' => 'flatrate',
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
                    'token' => $this->customerToken,
                ],
            ];
        }

        $body = [
            'email' => 'customer@example.com',
            'paymentMethod' => ['method' => 'checkmo'],
        ];

        if (!$billingSameAsShipping) {
            $body['billing_address'] = $this->getBaseAddressData();
        }

        return (int)$this->_webApiCall($serviceInfo, $body, null, $this->storeViewCode);
    }

    /**
     * @param int $customerId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @param string|null $websiteCode
     * @return CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerByEmail(string $customerEmail, $websiteCode = null): CustomerInterface
    {
        $websiteId = $websiteCode
            ? $this->objectManager->get(WebsiteRepositoryInterface::class)->get($websiteCode)->getId()
            : null;
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        /** @var CustomerRegistry $customerRegistry */
        $customerRegistry = $this->objectManager->get(CustomerRegistry::class);
        $customerRegistry->removeByEmail($customerEmail, $websiteId);

        return $customerRepository->get($customerEmail, $websiteId);
    }

    /**
     * Get fresh address list;
     *
     * @param string|null $websiteCode
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCustomerAddressList($websiteCode = null): array
    {
        $customer = $this->getCustomerByEmail('customer@example.com', $websiteCode);

        /** @var \Magento\Customer\Api\AddressRepositoryInterface $addressRepo */
        $addressRepo = $this->objectManager->get(\Magento\Customer\Api\AddressRepositoryInterface::class);
        /** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        /** @var \Magento\Framework\Api\FilterBuilder $filterBuilder */
        $filterBuilder = $this->objectManager->get(\Magento\Framework\Api\FilterBuilder::class);

        $filter = $filterBuilder->setField('parent_id')
            ->setValue($customer->getId())
            ->setConditionType('eq')
            ->create();
        $addresses = (array)($addressRepo->getList(
            $searchCriteriaBuilder->addFilters([$filter])->create()
        )->getItems());

        return $addresses;
    }
}

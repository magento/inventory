<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\EstimateAddressInterface;
use Magento\Quote\Api\Data\EstimateAddressInterfaceFactory;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Check if In-Store Pickup delivery method is applicable for a cart by cartId.
 *
 * @api
 */
class IsInStorePickupDeliveryAvailableForCart
{
    private const CARRIER_CODE = 'in_store';
    private const XML_PATH_DEFAULT_COUNTRY = 'general/country/default';

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ShippingMethodManagementInterface
     */
    private $shippingMethodManagement;

    /**
     * @var EstimateAddressInterfaceFactory
     */
    private $estimateAddressFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CartRepositoryInterface $cartRepository
     * @param ShippingMethodManagementInterface $shippingMethodManagement
     * @param EstimateAddressInterfaceFactory $estimateAddressFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CartRepositoryInterface $cartRepository,
        ShippingMethodManagementInterface $shippingMethodManagement,
        EstimateAddressInterfaceFactory $estimateAddressFactory
    ) {
        $this->cartRepository = $cartRepository;
        $this->scopeConfig = $scopeConfig;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->estimateAddressFactory = $estimateAddressFactory;
    }

    /**
     * Check if In-Store Pickup delivery method is applicable for a cart by cartId.
     *
     * @param int $cartId
     *
     * @return bool
     */
    public function execute(int $cartId): bool
    {
        try {
            $cart = $this->cartRepository->get($cartId);
            $address = $this->getEstimateAddress($cart);
            // TODO: replace deprecated method usage
            foreach ($this->shippingMethodManagement->estimateByAddress($cartId, $address) as $method) {
                if ($method->getCarrierCode() == self::CARRIER_CODE) {
                    return true;
                }
            }

            return false;
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * Return default country code
     *
     * @return string|null
     */
    private function getDefaultCountry(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_DEFAULT_COUNTRY, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get shipping address and make sure countryId is not empty.
     *
     * @param CartInterface $cart
     *
     * @return EstimateAddressInterface
     */
    private function getEstimateAddress(CartInterface $cart): EstimateAddressInterface
    {
        /** @var ShippingAssignmentInterface|null $assignment */
        $assignment = current($cart->getExtensionAttributes()->getShippingAssignments());
        $data = ['country_id' => $this->getDefaultCountry()];
        if ($assignment) {
            $shippingAddress = $assignment->getShipping()->getAddress();

            $data = [
                'country_id' => $shippingAddress->getCountryId() ?: $data['country_id'],
                'postcode' => $shippingAddress->getPostcode(),
                'region' => $shippingAddress->getRegion(),
                'region_id' => $shippingAddress->getRegionId()
            ];
        }

        return $this->estimateAddressFactory->create(['data' => $data]);
    }
}

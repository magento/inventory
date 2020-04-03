<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryAvailableForCartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\EstimateAddressInterface;
use Magento\Quote\Api\Data\EstimateAddressInterfaceFactory;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @inheritDoc
 */
class IsInStorePickupDeliveryAvailableForCart implements IsInStorePickupDeliveryAvailableForCartInterface
{
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
     * @var EstimateAddressInterfaceFactory
     */
    private $estimateAddressFactory;

    /**
     * @var RateRequestFactory
     */
    private $rateRequestFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var InStorePickup
     */
    private $inStorePickup;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CartRepositoryInterface $cartRepository
     * @param EstimateAddressInterfaceFactory $estimateAddressFactory
     * @param RateRequestFactory $rateRequestFactory
     * @param StoreManagerInterface $storeManager
     * @param InStorePickup $inStorePickup
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CartRepositoryInterface $cartRepository,
        EstimateAddressInterfaceFactory $estimateAddressFactory,
        RateRequestFactory $rateRequestFactory,
        StoreManagerInterface $storeManager,
        InStorePickup $inStorePickup
    ) {
        $this->cartRepository = $cartRepository;
        $this->scopeConfig = $scopeConfig;
        $this->estimateAddressFactory = $estimateAddressFactory;
        $this->rateRequestFactory = $rateRequestFactory;
        $this->storeManager = $storeManager;
        $this->inStorePickup = $inStorePickup;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $cartId): bool
    {
        try {
            $cart = $this->cartRepository->get($cartId);
            $address = $this->getEstimateAddress($cart);
            $rateRequest = $this->getRateRequest($address, $cart);
            $isAvailable = $this->inStorePickup->collectRates($rateRequest) &&
                $this->inStorePickup->processAdditionalValidation($rateRequest) === true;
        } catch (NoSuchEntityException $e) {
            $isAvailable = false;
        }

        return $isAvailable;
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

    /**
     * Collect rates by address
     *
     * @param EstimateAddressInterface $address
     * @param CartInterface $cart
     *
     * @return RateRequest
     * @throws NoSuchEntityException
     */
    private function getRateRequest(EstimateAddressInterface $address, CartInterface $cart): RateRequest
    {
        /** @var $request RateRequest */
        $request = $this->rateRequestFactory->create();
        $request->setAllItems($cart->getAllItems());
        $request->setDestCountryId($address->getCountryId());
        $request->setDestRegionId($address->getRegionId());
        $request->setDestPostcode($address->getPostcode());
        $request->setPackageValue($cart->getBaseSubtotal());
        $request->setPackageValueWithDiscount($cart->getBaseSubtotalWithDiscount());
        $request->setPackageQty($this->getItemQty($cart));

        $store = $this->storeManager->getStore();
        $request->setStoreId($store->getId());
        $request->setWebsiteId($store->getWebsiteId());
        $request->setBaseCurrency($store->getBaseCurrency());
        $request->setPackageCurrency($store->getCurrentCurrency());

        return $request;
    }

    /**
     * Retrieve item quantity by id
     *
     * @param CartInterface $cart
     *
     * @return float
     */
    private function getItemQty(CartInterface $cart): float
    {
        $qty = 0.0;
        /** @var Item $item */
        foreach ($cart->getAllItems() as $item) {
            $qty += $item->getQty();
        }

        return $qty;
    }
}

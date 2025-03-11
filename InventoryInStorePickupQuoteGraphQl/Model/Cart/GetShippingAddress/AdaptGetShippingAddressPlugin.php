<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuoteGraphQl\Model\Cart\GetShippingAddress;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Inventory\Model\SourceRepository;
use Magento\Quote\Api\Data\AddressExtensionFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\QuoteGraphQl\Model\Cart\GetShippingAddress;

/**
 * Set shipping address to the cart. Proceed with passed Pickup Location code.
 */
class AdaptGetShippingAddressPlugin
{
    /**
     * AdaptGetShippingAddressPlugin Constructor
     *
     * @param AddressExtensionFactory $addressExtensionFactory
     * @param SourceRepository $sourceRepository
     */
    public function __construct(
        private readonly AddressExtensionFactory $addressExtensionFactory,
        private readonly SourceRepository        $sourceRepository
    ) {
    }

    /**
     * Set shipping address in quote from pickup_location_code
     *
     * @param GetShippingAddress $subject
     * @param ContextInterface $context
     * @param array $shippingAddressInput
     * @return array
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(
        GetShippingAddress $subject,
        ContextInterface $context,
        array $shippingAddressInput
    ): array {
        try {
            if (!empty($shippingAddressInput['pickup_location_code']) &&
                empty($shippingAddressInput['customer_address_id']) &&
                empty($shippingAddressInput['address'])
            ) {
                $address = $this->getStoreAddress($shippingAddressInput['pickup_location_code']);
                if ($address) {
                    $shippingAddressInput['address'] = $address;
                }
            }
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        return [$context, $shippingAddressInput];
    }

    /**
     * Get shipping address for store pickup based on the input.
     *
     * @param GetShippingAddress $subject
     * @param Address $result
     * @param ContextInterface $context
     * @param array $shippingAddressInput
     * @return Address
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        GetShippingAddress $subject,
        Address $result,
        ContextInterface $context,
        array $shippingAddressInput
    ): Address {
        $this->assignPickupLocation($result, $shippingAddressInput);

        return $result;
    }

    /**
     * Set to Quote Address Pickup Location Code, if it was provided.
     *
     * @param Address $address
     * @param array $shippingAddressInput
     */
    private function assignPickupLocation(Address $address, array $shippingAddressInput): void
    {
        $pickupLocationCode = $shippingAddressInput['pickup_location_code'] ?? null;

        if ($pickupLocationCode === null) {
            return;
        }

        $extension = $address->getExtensionAttributes();
        if (!$extension) {
            $extension = $this->addressExtensionFactory->create();
            $address->setExtensionAttributes($extension);
        }

        $extension->setPickupLocationCode($pickupLocationCode);
    }

    /**
     * Get store address
     *
     * @param string $pickupLocationCode
     * @return array
     * @throws NoSuchEntityException
     */
    private function getStoreAddress(string $pickupLocationCode): array
    {
        $storeAddress = $this->sourceRepository->get($pickupLocationCode);
        if ($storeAddress->getEnabled() && $storeAddress->getIsPickupLocationActive()) {
            return [
                'firstname' => $storeAddress->getFrontendName(),
                'lastname' => 'Store',
                'street' => $storeAddress->getStreet(),
                'city' => $storeAddress->getCity(),
                'region' => $storeAddress->getRegionId(),
                'postcode' => $storeAddress->getPostcode(),
                'country_code' => $storeAddress->getCountryId(),
                'telephone' => $storeAddress->getPhone()
            ];
        }

        return [];
    }
}

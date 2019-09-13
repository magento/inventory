<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuoteGraphQl\Model\Cart;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\GraphQl\Model\Query\ContextInterface;
use Magento\Quote\Api\Data\AddressExtensionFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\QuoteGraphQl\Model\Cart\AssignShippingAddressToCart;
use Magento\QuoteGraphQl\Model\Cart\QuoteAddressFactory;
use Magento\QuoteGraphQl\Model\Cart\SetShippingAddressesOnCartInterface;

/**
 * Set shipping address to the cart. Proceed with passed Pickup Location code.
 */
class SetShippingAddressesOnCart implements SetShippingAddressesOnCartInterface
{
    /**
     * @var AssignShippingAddressToCart
     */
    private $assignShippingAddressToCart;

    /**
     * @var QuoteAddressFactory
     */
    private $quoteAddressFactory;

    /**
     * @var AddressExtensionFactory
     */
    private $addressExtensionFactory;

    /**
     * @param AssignShippingAddressToCart $assignShippingAddressToCart
     * @param QuoteAddressFactory $quoteAddressFactory
     * @param AddressExtensionFactory $addressExtensionFactory
     */
    public function __construct(
        AssignShippingAddressToCart $assignShippingAddressToCart,
        QuoteAddressFactory $quoteAddressFactory,
        AddressExtensionFactory $addressExtensionFactory
    ) {
        $this->assignShippingAddressToCart = $assignShippingAddressToCart;
        $this->quoteAddressFactory = $quoteAddressFactory;
        $this->addressExtensionFactory = $addressExtensionFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(
        ContextInterface $context,
        CartInterface $cart,
        array $shippingAddressesInput
    ): void {
        if (count($shippingAddressesInput) > 1) {
            throw new GraphQlInputException(
                __('You cannot specify multiple shipping addresses.')
            );
        }
        $shippingAddressInput = current($shippingAddressesInput);
        $shippingAddress = $this->getShippingAddress($shippingAddressInput, $context);
        $this->assignPickupLocation($shippingAddress, $shippingAddressInput);

        $this->assignShippingAddressToCart->execute($cart, $shippingAddress);
    }

    /**
     * Prepare Quote Address object, based on provided input.
     *
     * @param array $shippingAddressInput
     * @param ContextInterface $context
     *
     * @return Address
     * @throws GraphQlAuthorizationException
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
     */
    private function getShippingAddress(array $shippingAddressInput, ContextInterface $context): Address
    {
        $customerAddressId = $shippingAddressInput['customer_address_id'] ?? null;
        $addressInput = $shippingAddressInput['address'] ?? null;

        if (null === $customerAddressId && null === $addressInput) {
            throw new GraphQlInputException(
                __('The shipping address must contain either "customer_address_id" or "address".')
            );
        }

        if ($customerAddressId && $addressInput) {
            throw new GraphQlInputException(
                __('The shipping address cannot contain "customer_address_id" and "address" at the same time.')
            );
        }

        if (null === $customerAddressId) {
            $addressInput['country_code'] = strtoupper($addressInput['country_code']);
            $shippingAddress = $this->quoteAddressFactory->createBasedOnInputData($addressInput);
        } else {
            if (false === $context->getExtensionAttributes()->getIsCustomer()) {
                throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
            }

            $shippingAddress = $this->quoteAddressFactory->createBasedOnCustomerAddress(
                (int)$customerAddressId,
                $context->getUserId()
            );
        }

        return $shippingAddress;
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
}

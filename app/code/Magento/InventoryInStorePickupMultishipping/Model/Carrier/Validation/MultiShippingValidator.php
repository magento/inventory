<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupMultishipping\Model\Carrier\Validation;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Validation\RequestValidatorInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Checkout\Model\Cart;

/**
 * @inheritdoc
 */
class MultiShippingValidator implements RequestValidatorInterface
{

    /**
     * @var Cart
     */
    private $checkoutSession;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param Cart $checkoutSession
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        Cart $checkoutSession
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function validate(RateRequest $rateRequest): ValidationResult
    {
        $errors = [];

        $quote = $this->checkoutSession->getQuote();

        if ($quote->getIsMultiShipping()) {
            $errors[] = __('No Pickup Locations available for Multi Shipping');
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

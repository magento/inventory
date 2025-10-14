<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model\Carrier\Validation;

use Magento\Framework\Validation\ValidationResult;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * Responsible for validation of rate request for In-Store Pickup Delivery.
 *
 * @api
 */
interface RequestValidatorInterface
{
    /**
     * Validate rate request for In-Store Pickup Delivery.
     *
     * @param RateRequest $rateRequest
     * @return ValidationResult
     */
    public function validate(RateRequest $rateRequest): ValidationResult;
}

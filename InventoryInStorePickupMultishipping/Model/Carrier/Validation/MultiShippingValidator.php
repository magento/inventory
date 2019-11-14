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
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory
    ) {
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(RateRequest $rateRequest): ValidationResult
    {
        $errors = [];
        /** @var \Magento\Quote\Model\Quote\Item\AbstractItem[] $items */
        $items = $rateRequest->getAllItems();
        $item = is_array($items) ? current($items) : $items;

        if ($item && $item->getQuote()->getIsMultiShipping()) {
            $errors[] = __('In-Store Pickup is not available with multiple address checkout.');
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingApi\Model\Carrier\Validation;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Validation\RequestValidatorInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * @inheritdoc
 */
class RequestValidatorChain implements RequestValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var RequestValidatorInterface[]
     */
    private $validators;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param array $validators
     *
     * @throws LocalizedException
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        array $validators = []
    ) {
        $this->validationResultFactory = $validationResultFactory;

        foreach ($validators as $validator) {
            if (!$validator instanceof RequestValidatorInterface) {
                throw new LocalizedException(
                    __(
                        'In-Store Pickup Carrier Rate Request Validator must implement %1.',
                        RequestValidatorInterface::class
                    )
                );
            }
        }
        $this->validators = $validators;
    }

    /**
     * @inheritdoc
     */
    public function validate(RateRequest $rateRequest): ValidationResult
    {
        $errors = [];
        /** @var RequestValidatorInterface $validator */
        foreach ($this->validators as $validator) {
            $validationResult = $validator->validate($rateRequest);

            if (!$validationResult->isValid()) {
                $errors = array_merge($errors, $validationResult->getErrors());
            }
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

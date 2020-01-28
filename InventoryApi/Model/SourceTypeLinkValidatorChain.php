<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validation\ValidationResult;

/**
 * Chain of validators for stock source link. Extension point for new validators via di configuration
 */
class SourceTypeLinkValidatorChain implements SourceTypeLinkValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var array
     */
    private $validators;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param array $validators
     * @throws LocalizedException
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        array $validators = []
    ) {
        $this->validationResultFactory = $validationResultFactory;

        foreach ($validators as $validator) {
            if (!$validator instanceof SourceTypeLinkValidatorInterface) {
                throw new LocalizedException(
                    __('Validator must implement SourceTypeLinkValidatorInterface.')
                );
            }
        }
        $this->validators = $validators;
    }

    /**
     * @inheritDoc
     */
    public function validate(SourceTypeLinkInterface $link): ValidationResult
    {
        $errors = [];

        /** @var SourceTypeLinkValidatorInterface $validator */
        foreach ($this->validators as $validator) {
            $validationResult = $validator->validate($link);

            if (!$validationResult->isValid()) {
                $errors[] = $validationResult->getErrors();
            }
        }
        $errors = !empty($errors) ? array_merge(...$errors) : $errors;

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

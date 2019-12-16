<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Validators\IsNumericValue;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Model\SourceItemValidatorInterface;

/**
 * Check that quantity is valid
 */
class QuantityValidator implements SourceItemValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var IsNumericValue
     */
    private $isNumericValue;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param IsNumericValue $isNumericValue
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        IsNumericValue $isNumericValue
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->isNumericValue = $isNumericValue;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $source): ValidationResult
    {
        $value = $source->getQuantity();
        $errors = [
            $this->isNumericValue->execute(SourceItemInterface::QUANTITY, $value)
        ];
        $errors = !empty($errors) ? array_merge(...$errors) : $errors;

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

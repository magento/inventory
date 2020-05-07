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
 * Check that status is valid
 */
class StatusValidator implements SourceItemValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var array
     */
    private $allowedSourceItemStatuses;

    /**
     * @var IsNumericValue
     */
    private $isNumericValue;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param IsNumericValue $isNumericValue
     * @param array $allowedSourceItemStatuses
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        IsNumericValue $isNumericValue,
        array $allowedSourceItemStatuses = []
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->allowedSourceItemStatuses = $allowedSourceItemStatuses;
        $this->isNumericValue = $isNumericValue;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $source): ValidationResult
    {
        $value = $source->getStatus();
        $errors = [
            $this->isNumericValue->execute(SourceItemInterface::STATUS, $value)
        ];

        if (!in_array((int)$value, array_values($this->allowedSourceItemStatuses), true)) {
            $errors[] = [
                __('"%field" should a known status.', ['field' => SourceItemInterface::STATUS])
            ];
        }
        $errors = !empty($errors) ? array_merge(...$errors) : $errors;

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Source\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Validators\NoSpecialCharsInString;
use Magento\Inventory\Model\Validators\NotAnEmptyString;
use Magento\Inventory\Model\Validators\NoWhitespaceInString;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Model\SourceValidatorInterface;

/**
 * Check that code is valid
 */
class CodeValidator implements SourceValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var NotAnEmptyString
     */
    private $notAnEmptyString;

    /**
     * @var NoWhitespaceInString
     */
    private $noWhitespaceInString;

    /**
     * @var NoSpecialCharsInString
     */
    private $noSpecialCharsInString;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param NotAnEmptyString $notAnEmptyString
     * @param NoWhitespaceInString $noWhitespaceInString
     * @param NoSpecialCharsInString $noSpecialCharsInString
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        NotAnEmptyString $notAnEmptyString,
        NoWhitespaceInString $noWhitespaceInString,
        NoSpecialCharsInString $noSpecialCharsInString
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->notAnEmptyString = $notAnEmptyString;
        $this->noWhitespaceInString = $noWhitespaceInString;
        $this->noSpecialCharsInString = $noSpecialCharsInString;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceInterface $source): ValidationResult
    {
        $value = (string)$source->getSourceCode();
        $errors = [
            $this->notAnEmptyString->execute(SourceInterface::SOURCE_CODE, $value),
            $this->noWhitespaceInString->execute(SourceInterface::SOURCE_CODE, $value),
            $this->noSpecialCharsInString->execute($value)
        ];
        $errors = !empty($errors) ? array_merge(...$errors) : $errors;

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

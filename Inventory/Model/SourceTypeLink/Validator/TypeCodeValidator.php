<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceTypeLink\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Validators\NoSpecialCharsInString;
use Magento\Inventory\Model\Validators\NotAnEmptyString;
use Magento\Inventory\Model\Validators\NoWhitespaceInString;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\InventoryApi\Model\SourceTypeLinkValidatorInterface;

/**
 * Check that code is valid
 */
class TypeCodeValidator implements SourceTypeLinkValidatorInterface
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
     * @inheritDoc
     */
    public function validate(SourceTypeLinkInterface $link): ValidationResult
    {
        $value = (string)$link->getTypeCode();
        $errors = [
            $this->notAnEmptyString->execute(SourceTypeLinkInterface::TYPE_CODE, $value),
            $this->noWhitespaceInString->execute(SourceTypeLinkInterface::TYPE_CODE, $value),
            $this->noSpecialCharsInString->execute($value)
        ];
        $errors = !empty($errors) ? array_merge(...$errors) : $errors;

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

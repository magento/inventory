<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\StockSourceLink\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Model\StockSourceLinkValidatorInterface;

/**
 * Responsible for Stock Source links validation
 */
class StockSourceLinksValidator
{
    /**
     * @var StockSourceLinkValidatorInterface
     */
    private $stockSourceLinkValidator;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param StockSourceLinkValidatorInterface $stockSourceLinkValidator
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        StockSourceLinkValidatorInterface $stockSourceLinkValidator
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->stockSourceLinkValidator = $stockSourceLinkValidator;
    }

    /**
     * Validates an array of stock source links and returns a `ValidationResult` with any detected validation errors.
     *
     * @param StockSourceLinkInterface[] $links
     * @return ValidationResult
     */
    public function validate(array $links): ValidationResult
    {
        $errors = [[]];
        foreach ($links as $sourceItem) {
            $validationResult = $this->stockSourceLinkValidator->validate($sourceItem);
            if (!$validationResult->isValid()) {
                $errors[] = $validationResult->getErrors();
            }
        }
        $errors = array_merge(...$errors);

        $validationResult = $this->validationResultFactory->create(['errors' => $errors]);
        return $validationResult;
    }
}

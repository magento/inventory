<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Validators\NotAnEmptyString;
use Magento\Inventory\Model\Validators\NotExistentSku;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Model\SourceItemValidatorInterface;

/**
 * Check that sku is valid
 */
class SkuValidator implements SourceItemValidatorInterface
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
     * @var NotExistentSku
     */
    private $NotExistentSku;


    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param NotAnEmptyString $notAnEmptyString
     * @param NotExistentSku $notExistentSku
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        NotAnEmptyString $notAnEmptyString,
        NotExistentSku $notExistentSku
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->notAnEmptyString = $notAnEmptyString;
        $this->NotExistentSku = $notExistentSku;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $source): ValidationResult
    {
        $value = $source->getSku();
        $errors = [
            $this->notAnEmptyString->execute(SourceItemInterface::SKU, (string)$value),
            $this->NotExistentSku->execute(SourceItemInterface::SKU, (string)$value)
        ];
        $errors = !empty($errors) ? array_merge(...$errors) : $errors;

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

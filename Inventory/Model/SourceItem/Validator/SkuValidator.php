<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\Validators\NoSpaceBeforeAndAfterString;
use Magento\Inventory\Model\Validators\NotAnEmptyString;
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
     * @var NoSpaceBeforeAndAfterString
     */
    private $noSpaceBeforeAndAfterString;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param NotAnEmptyString $notAnEmptyString
     * @param NoSpaceBeforeAndAfterString $noSpaceBeforeAndAfterString
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        NotAnEmptyString $notAnEmptyString,
        NoSpaceBeforeAndAfterString $noSpaceBeforeAndAfterString
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->notAnEmptyString = $notAnEmptyString;
        $this->noSpaceBeforeAndAfterString = $noSpaceBeforeAndAfterString;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $source): ValidationResult
    {
        $value = $source->getSku();
        $errors = [
            $this->notAnEmptyString->execute(SourceItemInterface::SKU, (string)$value),
            $this->noSpaceBeforeAndAfterString->execute(SourceItemInterface::SKU, (string)$value)
        ];
        $errors = array_merge(...$errors);
        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

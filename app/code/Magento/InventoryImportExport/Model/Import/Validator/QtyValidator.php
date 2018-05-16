<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryImportExport\Model\Import\Sources;

/**
 * Extension point for row validation
 */
class QtyValidator implements ValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(ValidationResultFactory $validationResultFactory)
    {
        $this->validationResultFactory = $validationResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(array $rowData, int $rowNumber): ValidationResult
    {
        $errors = [];

        if (!isset($rowData[Sources::COL_QTY])) {
            $errors[] = __('Missing required column "%column"', ['column' => Sources::COL_QTY]);
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

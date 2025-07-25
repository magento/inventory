<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Validator;

use Magento\Framework\Validation\ValidationResult;

/**
 * Extension point for row validation (Service Provider Interface - SPI)
 *
 * @api
 */
interface ValidatorInterface
{
    /**
     * Validates a row of data and returns a `ValidationResult` indicating whether the row passes validation.
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return ValidationResult
     */
    public function validate(array $rowData, int $rowNumber);
}

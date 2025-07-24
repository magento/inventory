<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

use Magento\Framework\Validation\ValidationResult;

/**
 * Responsible for Product Source un-assignment validation
 *
 * @api
 */
interface BulkSourceUnassignValidatorInterface
{
    /**
     * Validates a mass un-assignment request
     *
     * @param array $skus
     * @param array $sourceCodes
     * @return ValidationResult
     */
    public function validate(array $skus, array $sourceCodes): ValidationResult;
}

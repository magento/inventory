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
interface BulkInventoryTransferValidatorInterface
{
    /**
     * Validates a mass un-assignment request
     *
     * @param string[] $skus
     * @param string $originSource
     * @param string $destinationSource
     * @return ValidationResult
     */
    public function validate(array $skus, string $originSource, string $destinationSource): ValidationResult;
}

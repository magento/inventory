<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\Framework\Validation\ValidationResult;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Responsible for Source item validation
 * Extension point for base validation
 *
 * @api
 */
interface SourceItemValidatorInterface
{
    /**
     * Validates a source item entity using predefined rules, returns a ValidationResult indicating validation success.
     *
     * @param SourceItemInterface $sourceItem
     * @return ValidationResult
     */
    public function validate(SourceItemInterface $sourceItem): ValidationResult;
}

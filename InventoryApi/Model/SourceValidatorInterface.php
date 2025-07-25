<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\Framework\Validation\ValidationResult;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Responsible for Source validation
 * Extension point for base validation
 *
 * @api
 */
interface SourceValidatorInterface
{
    /**
     * Validates a source entity using predefined rules and returns a ValidationResult indicating validation success.
     *
     * @param SourceInterface $source
     * @return ValidationResult
     */
    public function validate(SourceInterface $source): ValidationResult;
}

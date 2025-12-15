<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\Framework\Validation\ValidationResult;
use Magento\InventoryApi\Api\Data\StockInterface;

/**
 * Responsible for Stock validation
 * Extension point for base validation
 *
 * @api
 */
interface StockValidatorInterface
{
    /**
     * Validates a stock entity using predefined rules and returns a ValidationResult indicating validation success.
     *
     * @param StockInterface $stock
     * @return ValidationResult
     */
    public function validate(StockInterface $stock): ValidationResult;
}

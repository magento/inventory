<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\Framework\Validation\ValidationResult;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;

/**
 * Responsible for Stock Source link validation
 * Extension point for base validation
 *
 * @api
 */
interface StockSourceLinkValidatorInterface
{
    /**
     * Validates a stock source link and returns a `ValidationResult` indicating whether the link is valid or not.
     *
     * @param StockSourceLinkInterface $link
     * @return ValidationResult
     */
    public function validate(StockSourceLinkInterface $link): ValidationResult;
}

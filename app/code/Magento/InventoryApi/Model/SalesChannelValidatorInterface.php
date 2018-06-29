<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\Framework\Validation\ValidationResult;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Responsible for Sales Channel validation
 * Extension point for base validation
 *
 * @api
 */
interface SalesChannelValidatorInterface
{
    /**
     * @param SalesChannelInterface $salesChannel
     * @return ValidationResult
     */
    public function validate(SalesChannelInterface $salesChannel): ValidationResult;
}

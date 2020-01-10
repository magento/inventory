<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\Framework\Validation\ValidationResult;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;

/**
 * Responsible for Source Type link validation
 * Extension point for base validation
 *
 */
interface SourceTypeLinkValidatorInterface
{
    /**
     * @param SourceTypeLinkInterface $link
     * @return ValidationResult
     */
    public function validate(SourceTypeLinkInterface $link): ValidationResult;
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

/**
 * Is source allowed for current user service.
 */
interface IsSourceAllowedForUserInterface
{
    /**
     * Verify, source allowed for current admin user.
     *
     * @param string $sourceCode
     * @return bool
     */
    public function execute(string $sourceCode): bool;
}

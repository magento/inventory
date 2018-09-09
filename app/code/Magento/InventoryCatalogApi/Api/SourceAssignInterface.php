<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Api;

/**
 * Perform product source assignment
 *
 * @api
 */
interface SourceAssignInterface
{
    /**
     * Assign a product to source and return the number of source items created
     * @param string $sku
     * @param string $sourceCode
     * @return bool
     */
    public function execute(string $sku, string $sourceCode): bool;
}

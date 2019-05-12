<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * All APIs to retrieve Backorder Config Value on the level of SourceItem/Source/Globally
 * @api
 */
interface GetBackorderStatusConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param string $sourceCode
     * @return ?int
     */
    public function forSourceItem(string $sku, string $sourceCode): ?int;

    /**
     * @param string $sourceCode
     * @return ?int
     */
    public function forSource(string $sourceCode): ?int;

    /**
     * @param string $sourceCode
     * @return int
     */
    public function forGlobal(): int;
}

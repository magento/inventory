<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Get backorder on the level of SourceItem/Source/Global
 *
 * @api
 */
interface GetBackordersConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param string $sourceCode
     * @return int|null
     */
    public function forSourceItem(string $sku, string $sourceCode): ?int;

    /**
     * @param string $sourceCode
     * @return int|null
     */
    public function forSource(string $sourceCode): ?int;

    /**
     * @return int
     */
    public function forGlobal(): int;
}

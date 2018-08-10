<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Get notify stock qty on the level of SourceItem/Source/Global
 *
 * @api
 */
interface GetNotifyStockQtyConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param string $sourceCode
     * @return float|null
     */
    public function forSourceItem(string $sku, string $sourceCode): ?float;

    /**
     * @param string $sourceCode
     * @return float|null
     */
    public function forSource(string $sourceCode): ?float;

    /**
     * @return float
     */
    public function forGlobal(): float;
}

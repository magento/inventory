<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * All APIs to specify Backorder Config Value on the level of SourceItem/Source/Globally
 * @api
 */
interface SetBackorderStatusConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param string $sourceCode Backorder
     * @param int $backorderStatus if NULL is set that means fallback to Source configuration would be used
     * @return void
     */
    public function forSourceItem(string $sku, string $sourceCode, ?int $backorderStatus): void;

    /**
     * @param string $sourceCode
     * @param int $backorderStatus if NULL is set that means fallback to Global configuration would be used
     * @return void
     */
    public function forSource(string $sourceCode, ?int $backorderStatus): void;

    /**
     * @param int $backorderStatus Backorder configuration applied globally
     * @return void
     */
    public function forGlobal(int $backorderStatus): void;
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryConfigurationApi\Api\GetBackorderStatusConfigurationValueInterface;

class GetBackorderStatusConfigurationValue implements GetBackorderStatusConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param string $sourceCode
     * @return ?int
     */
    public function forSourceItem(string $sku, string $sourceCode): ?int
    {
        // TODO: Implement forSourceItem() method.
    }

    /**
     * @param string $sourceCode
     * @return ?int
     */
    public function forSource(string $sourceCode): ?int
    {
        // TODO: Implement forSource() method.
    }

    /**
     * @param string $sourceCode
     * @return int
     */
    public function forGlobal(): int
    {
        // TODO: Implement forGlobal() method.
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;

interface GetSourceConfigurationInterface
{
    /**
     * @param string $sku
     * @param string $sourceCode
     * @return SourceItemConfigurationInterface
     */
    public function forSourceItem(string $sku, string $sourceCode): SourceItemConfigurationInterface;

    /**
     * @param string $sourceCode
     * @return SourceItemConfigurationInterface
     */
    public function forSource(string $sourceCode): SourceItemConfigurationInterface;

    /**
     * @return SourceItemConfigurationInterface
     */
    public function forGlobal(): SourceItemConfigurationInterface;
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;

interface SaveSourceItemConfigurationInterface
{
    /**
     * @param string $sku
     * @param string $sourceCode
     * @param SourceItemConfigurationInterface $sourceItemConfiguration
     * @return void
     */
    public function forSourceItem(
        string $sku,
        string $sourceCode,
        SourceItemConfigurationInterface $sourceItemConfiguration
    ): void;

    /**
     * @param string $sourceCode
     * @param SourceItemConfigurationInterface $sourceItemConfiguration
     * @return void
     */
    public function forSource(string $sourceCode, SourceItemConfigurationInterface $sourceItemConfiguration): void;

    /**
     * @param SourceItemConfigurationInterface $sourceItemConfiguration
     * @return void
     */
    public function forGlobal(SourceItemConfigurationInterface $sourceItemConfiguration): void;
}

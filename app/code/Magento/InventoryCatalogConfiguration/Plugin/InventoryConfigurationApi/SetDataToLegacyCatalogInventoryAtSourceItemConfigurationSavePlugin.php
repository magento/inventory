<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogConfiguration\Plugin\InventoryConfigurationApi;

use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;

class SetDataToLegacyCatalogInventoryAtSourceItemConfigurationSavePlugin
{
    /**
     * @var SaveSourceConfigurationInterface
     */
    private $saveSourceConfiguration;

    /**
     * @param SaveSourceConfigurationInterface $saveSourceConfiguration
     */
    public function __construct(
        SaveSourceConfigurationInterface $saveSourceConfiguration
    ) {
        $this->saveSourceConfiguration = $saveSourceConfiguration;
    }

    /**
     * @param SaveSourceConfigurationInterface $subject
     * @param callable $proceed
     * @param string $sku
     * @param string $sourceCode
     * @param SourceItemConfigurationInterface $sourceItemConfiguration
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundForSourceItem(
        SaveSourceConfigurationInterface $subject,
        callable $proceed,
        string $sku,
        string $sourceCode,
        SourceItemConfigurationInterface $sourceItemConfiguration
    ):void {
        $this->saveSourceConfiguration->forSourceItem(
            $sku,
            $sourceCode,
            $sourceItemConfiguration
        );
    }
}

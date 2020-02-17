<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationInterfaceFactory;

/**
 * Get source items configurations by sku model.
 */
class GetBySku
{
    /**
     * @var ResourceModel\SourceItemConfiguration\GetBySku
     */
    private $getBySku;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var SourceItemConfigurationInterfaceFactory
     */
    private $sourceItemConfigurationInterfaceFactory;

    /**
     * @param ResourceModel\SourceItemConfiguration\GetBySku $getBySku
     * @param DataObjectHelper $dataObjectHelper
     * @param SourceItemConfigurationInterfaceFactory $sourceItemConfigurationInterfaceFactory
     */
    public function __construct(
        ResourceModel\SourceItemConfiguration\GetBySku $getBySku,
        DataObjectHelper $dataObjectHelper,
        SourceItemConfigurationInterfaceFactory $sourceItemConfigurationInterfaceFactory
    ) {
        $this->getBySku = $getBySku;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->sourceItemConfigurationInterfaceFactory = $sourceItemConfigurationInterfaceFactory;
    }

    /**
     * Retrieve source items configurations for given product sku.
     *
     * @param string $sku
     * @return SourceItemConfigurationInterface[]
     */
    public function execute(string $sku): array
    {
        $sourceItemsConfigurations = [];
        foreach ($this->getBySku->execute($sku) as $sourceItemConfigurationData) {
            $sourceItemConfiguration = $this->sourceItemConfigurationInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $sourceItemConfiguration,
                $sourceItemConfigurationData,
                SourceItemConfigurationInterface::class
            );
            $sourceItemsConfigurations[] = $sourceItemConfiguration;
        }

        return $sourceItemsConfigurations;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfiguration\Model\ResourceModel\GetSourceConfigurationData;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

class GetSourceConfiguration implements GetSourceConfigurationInterface
{
    /**
     * @var GetSourceConfigurationData
     */
    private $getSourceConfigurationData;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var SourceItemConfigurationInterfaceFactory
     */
    private $sourceItemConfigurationFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param GetSourceConfigurationData $getSourceConfigurationData
     * @param DataObjectHelper $dataObjectHelper
     * @param SourceItemConfigurationInterfaceFactory $sourceItemConfigurationFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        GetSourceConfigurationData $getSourceConfigurationData,
        DataObjectHelper $dataObjectHelper,
        SourceItemConfigurationInterfaceFactory $sourceItemConfigurationFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->getSourceConfigurationData = $getSourceConfigurationData;
        $this->sourceItemConfigurationFactory = $sourceItemConfigurationFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function forSourceItem(string $sku, string $sourceCode): SourceItemConfigurationInterface
    {
        $sourceItemConfigurationData = $this->getSourceConfigurationData->execute($sourceCode, $sku);
        /** @var SourceItemConfigurationInterface $sourceItemConfiguration */
        $sourceItemConfiguration = $this->sourceItemConfigurationFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $sourceItemConfiguration,
            $sourceItemConfigurationData,
            SourceItemConfigurationInterface::class
        );

        return $sourceItemConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function forSource(string $sourceCode): SourceItemConfigurationInterface
    {
        $sourceItemConfigurationData = $this->getSourceConfigurationData->execute($sourceCode);
        /** @var SourceItemConfigurationInterface $sourceItemConfiguration */
        $sourceItemConfiguration = $this->sourceItemConfigurationFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $sourceItemConfiguration,
            $sourceItemConfigurationData,
            SourceItemConfigurationInterface::class
        );

        return $sourceItemConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(): SourceItemConfigurationInterface
    {
        /** @var SourceItemConfigurationInterface $sourceItemConfiguration */
        $sourceItemConfiguration = $this->sourceItemConfigurationFactory->create();

        $sourceItemConfiguration->setNotifyStockQty(
            (float)$this->scopeConfig->getValue(SourceItemConfigurationInterface::XML_PATH_NOTIFY_STOCK_QTY)
        );

        $sourceItemConfiguration->setBackorders(
            (int)$this->scopeConfig->getValue(SourceItemConfigurationInterface::XML_PATH_BACKORDERS)
        );

        return $sourceItemConfiguration;
    }
}

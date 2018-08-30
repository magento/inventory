<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;

class SaveSourceConfigurationData implements ObserverInterface
{
    /**
     * @var SourceItemConfigurationInterfaceFactory
     */
    private $sourceItemConfigurationFactory;

    /**
     * @var SaveSourceConfigurationInterface
     */
    private $saveSourceConfiguration;

    /**
     * @param SourceItemConfigurationInterfaceFactory $sourceItemConfigurationFactory
     * @param SaveSourceConfigurationInterface $saveSourceConfiguration
     */
    public function __construct(
        SourceItemConfigurationInterfaceFactory $sourceItemConfigurationFactory,
        SaveSourceConfigurationInterface $saveSourceConfiguration
    ) {
        $this->sourceItemConfigurationFactory = $sourceItemConfigurationFactory;
        $this->saveSourceConfiguration = $saveSourceConfiguration;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $sourceCode = (string)$observer->getSource()->getSourceCode();
        $request = $observer->getRequest();

        $configOptions = $request->getParam('inventory_configuration');

        $sourceItemConfiguration = $this->sourceItemConfigurationFactory->create();

        if ($configOptions[SourceItemConfigurationInterface::BACKORDERS]['use_config_value']) {
            $sourceItemConfiguration->setBackorders(null);
        } else {
            $sourceItemConfiguration->setBackorders(
                (int)$configOptions[SourceItemConfigurationInterface::BACKORDERS]['value']
            );
        }

        if ($configOptions[SourceItemConfigurationInterface::NOTIFY_STOCK_QTY]['use_config_value']) {
            $sourceItemConfiguration->setNotifyStockQty(null);
        } else {
            $sourceItemConfiguration->setNotifyStockQty(
                (float)$configOptions[SourceItemConfigurationInterface::NOTIFY_STOCK_QTY]['value']
            );
        }

        $this->saveSourceConfiguration->forSource($sourceCode, $sourceItemConfiguration);
    }
}

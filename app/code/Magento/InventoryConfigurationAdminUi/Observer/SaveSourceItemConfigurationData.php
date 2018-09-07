<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;

/**
 * Save source item configuration for given product.
 */
class SaveSourceItemConfigurationData implements ObserverInterface
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
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $sku = $observer->getProduct()->getSku();
        $request = $observer->getController()->getRequest();
        $sources = $request->getParam('sources', []);
        $sources = $sources['assigned_sources'] ?? [];
        foreach ($sources as $source) {
            $sourceItemConfiguration = $this->sourceItemConfigurationFactory->create();
            $backorders = (int)$source['backorders_use_default'] !== 1 ? (int)$source['backorders'] : null ;
            $notifyStockQty = (int)$source['notify_stock_qty_use_default'] !== 1
                ? (float)$source['notify_stock_qty']
                : null;
            $sourceItemConfiguration->setBackorders($backorders);
            $sourceItemConfiguration->setNotifyStockQty($notifyStockQty);
            $this->saveSourceConfiguration->forSourceItem($sku, $source['source_code'], $sourceItemConfiguration);
        }
    }
}

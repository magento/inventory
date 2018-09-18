<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogConfiguration\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;

class SaveSourceItemConfiguration
{
    /**
     * @var SaveSourceConfigurationInterface
     */
    private $saveSourceConfiguration;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceItemConfiguration;

    /**
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param SaveSourceConfigurationInterface $saveSourceConfiguration
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        GetSourceConfigurationInterface $getSourceConfiguration,
        SaveSourceConfigurationInterface $saveSourceConfiguration,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->getSourceItemConfiguration = $getSourceConfiguration;
        $this->saveSourceConfiguration = $saveSourceConfiguration;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param string $sku
     * @param StockItemInterface $stockItem
     */
    public function execute(string $sku, StockItemInterface $stockItem): void
    {
        $sourceItemConfiguration = $this->getSourceItemConfiguration->forSourceItem(
            $sku,
            $this->defaultSourceProvider->getCode()
        );

        if ($stockItem->getData('use_config_' . SourceItemConfigurationInterface::BACKORDERS)
            || $stockItem->getData('use_config_' . SourceItemConfigurationInterface::BACKORDERS) === null) {
            $sourceItemConfiguration->setBackorders(null);
        } else {
            $backorders = $stockItem->getData(SourceItemConfigurationInterface::BACKORDERS) !== null
                ? (int)$stockItem->getData(SourceItemConfigurationInterface::BACKORDERS)
                : (int)$sourceItemConfiguration->getBackorders();
            $sourceItemConfiguration->setBackorders($backorders);
        }
        if ($stockItem->getData('use_config_' . SourceItemConfigurationInterface::NOTIFY_STOCK_QTY)
            || $stockItem->getData('use_config_' . SourceItemConfigurationInterface::NOTIFY_STOCK_QTY) === null) {
            $sourceItemConfiguration->setNotifyStockQty(null);
        } else {
            $notifyQty = $stockItem->getData(SourceItemConfigurationInterface::NOTIFY_STOCK_QTY) !== null
                ? (float)$stockItem->getData(SourceItemConfigurationInterface::NOTIFY_STOCK_QTY)
                : (float)$sourceItemConfiguration->getNotifyStockQty();
            $sourceItemConfiguration->setNotifyStockQty($notifyQty);
        }

        $this->saveSourceConfiguration->forSourceItem(
            $sku,
            $this->defaultSourceProvider->getCode(),
            $sourceItemConfiguration
        );
    }
}

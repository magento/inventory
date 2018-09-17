<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;
use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Save source item configuration for given product and default source after stock item was saved successfully.
 */
class SourceItemConfigurationImporterPlugin
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
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var StockItemInterfaceFactory
     */
    private $stockItemFactory;

    /**
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param SaveSourceConfigurationInterface $saveSourceConfiguration
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        GetSourceConfigurationInterface $getSourceConfiguration,
        SaveSourceConfigurationInterface $saveSourceConfiguration,
        DefaultSourceProviderInterface $defaultSourceProvider,
        StockItemInterfaceFactory $stockItemFactory,
        DataObjectHelper $dataObjectHelper
    ) {
        $this->getSourceItemConfiguration = $getSourceConfiguration;
        $this->saveSourceConfiguration = $saveSourceConfiguration;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->stockItemFactory = $stockItemFactory;
    }

    /**
     * After plugin Import to import Stock Data to Source Items
     *
     * @param StockItemImporterInterface $subject
     * @param null $result
     * @param array $stockData
     * @return void
     * @see StockItemImporterInterface::import()
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImport(
        StockItemImporterInterface $subject,
        $result,
        array $stockData
    ) {
        foreach ($stockData as $sku => $stockDatum) {
            $stockItem = $this->stockItemFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $stockItem,
                $stockDatum,
                StockItemInterface::class
            );

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
}

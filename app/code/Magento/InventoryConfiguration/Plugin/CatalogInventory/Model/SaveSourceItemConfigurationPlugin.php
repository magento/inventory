<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\CatalogInventory\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;

/**
 * Save source item configuration for given product and default source after stock item was saved successfully.
 */
class SaveSourceItemConfigurationPlugin
{
    /**
     * @var SaveSourceConfigurationInterface
     */
    private $saveSourceConfiguration;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

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
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        GetSourceConfigurationInterface $getSourceConfiguration,
        SaveSourceConfigurationInterface $saveSourceConfiguration,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->getSourceItemConfiguration = $getSourceConfiguration;
        $this->saveSourceConfiguration = $saveSourceConfiguration;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param StockItemRepositoryInterface $subject
     * @param StockItemInterface $stockItem
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        StockItemRepositoryInterface $subject,
        StockItemInterface $stockItem
    ): void {
        $productId = $stockItem->getProductId();
        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];
        $sourceItemConfiguration = $this->getSourceItemConfiguration->forSourceItem(
            $productSku,
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
            $productSku,
            $this->defaultSourceProvider->getCode(),
            $sourceItemConfiguration
        );
    }
}

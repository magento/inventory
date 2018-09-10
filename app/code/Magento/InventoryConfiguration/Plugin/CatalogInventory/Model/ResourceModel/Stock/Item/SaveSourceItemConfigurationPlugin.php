<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\CatalogInventory\Model\ResourceModel\Stock\Item;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;

/**
 * Save source item configuration for given product and default source after stock item was saved successfully.
 */
class SaveSourceItemConfigurationPlugin
{
    /**
     * @var SourceItemConfigurationInterfaceFactory
     */
    private $sourceItemConfigurationInterfaceFactory;

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
     * @param SourceItemConfigurationInterfaceFactory $sourceItemConfigurationInterfaceFactory
     * @param SaveSourceConfigurationInterface $saveSourceConfiguration
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        SourceItemConfigurationInterfaceFactory $sourceItemConfigurationInterfaceFactory,
        SaveSourceConfigurationInterface $saveSourceConfiguration,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->sourceItemConfigurationInterfaceFactory = $sourceItemConfigurationInterfaceFactory;
        $this->saveSourceConfiguration = $saveSourceConfiguration;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param ItemResourceModel $subject
     * @param ItemResourceModel $result
     * @param StockItemInterface $stockItem
     * @return ItemResourceModel
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ItemResourceModel $subject,
        ItemResourceModel $result,
        StockItemInterface $stockItem
    ): ItemResourceModel {
        $productId = $stockItem->getProductId();
        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];
        $sourceItemConfiguration = $this->sourceItemConfigurationInterfaceFactory->create();

        if ($stockItem->getData('use_config_' . SourceItemConfigurationInterface::BACKORDERS)) {
            $sourceItemConfiguration->setBackorders(null);
        } else {
            $sourceItemConfiguration->setBackorders(
                (int)$stockItem->getData(SourceItemConfigurationInterface::BACKORDERS)
            );
        }
        if ($stockItem->getData('use_config_' . SourceItemConfigurationInterface::NOTIFY_STOCK_QTY)) {
            $sourceItemConfiguration->setNotifyStockQty(null);
        } else {
            $sourceItemConfiguration->setNotifyStockQty(
                (float)$stockItem->getData(SourceItemConfigurationInterface::NOTIFY_STOCK_QTY)
            );
        }

        $this->saveSourceConfiguration->forSourceItem(
            $productSku,
            $this->defaultSourceProvider->getCode(),
            $sourceItemConfiguration
        );

        return $result;
    }
}

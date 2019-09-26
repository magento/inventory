<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Plugin\Catalog\Api\ProductRepositoryInterface;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventoryLowQuantityNotification\Model\SourceItemsConfigurationProcessor;
use Psr\Log\LoggerInterface;

/**
 * Process stock item configuration in single stock mode, after product has been saved.
 */
class ProcessStockItemConfiguration
{
    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var SourceItemsConfigurationProcessor
     */
    private $sourceItemsConfigurationProcessor;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param SourceItemsConfigurationProcessor $sourceItemsConfigurationProcessor
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        IsSingleSourceModeInterface $isSingleSourceMode,
        SourceItemsConfigurationProcessor $sourceItemsConfigurationProcessor,
        DefaultSourceProviderInterface $defaultSourceProvider,
        LoggerInterface $logger
    ) {
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->sourceItemsConfigurationProcessor = $sourceItemsConfigurationProcessor;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->logger = $logger;
    }

    /**
     * Save stock item configuration after product has been saved in single stock mode.
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(ProductRepositoryInterface $subject, ProductInterface $product): ProductInterface
    {
        if ($this->isSingleSourceMode->execute()
            && $this->isSourceItemManagementAllowedForProductType->execute($product->getTypeId()) === true) {
            $stockData = [];
            $stockItem = $product->getExtensionAttributes()->getStockItem();
            if ($stockItem) {
                $stockData[StockItemConfigurationInterface::NOTIFY_STOCK_QTY] = $stockItem->getNotifyStockQty();
                $stockData[StockItemConfigurationInterface::USE_CONFIG_NOTIFY_STOCK_QTY]
                    = $stockItem->getUseConfigNotifyStockQty();
            }
            $assignedSources[] = [
                SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
                StockItemConfigurationInterface::NOTIFY_STOCK_QTY =>
                    $stockData[StockItemConfigurationInterface::NOTIFY_STOCK_QTY] ?? 0,
                'notify_stock_qty_use_default' =>
                    $stockData[StockItemConfigurationInterface::USE_CONFIG_NOTIFY_STOCK_QTY] ?? 1,
            ];
            try {
                $this->sourceItemsConfigurationProcessor->process($product->getSku(), $assignedSources);
            } catch (InputException $e) {
                $this->logger->error(
                    __(
                        "Not able to save stock item configuration for product SKU: %1. " . PHP_EOL . $e->getMessage(),
                        $product->getSku()
                    )
                );
            }
        }

        return $product;
    }
}

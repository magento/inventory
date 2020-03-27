<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventoryLowQuantityNotification\Model\SourceItemsConfigurationProcessor;
use Psr\Log\LoggerInterface;

/**
 * Process default source item configuration after product has been saved.
 */
class ProcessDefaultSourceItemConfigurationPlugin
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
     * Update default source item configuration after product has been saved via Web-Api.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Product $subject, Product $result, AbstractModel $product): Product
    {
        if ($this->isSourceItemManagementAllowedForProductType->execute($product->getTypeId()) === false
            || !$product->getExtensionAttributes()->getStockItem()) {
            return $result;
        }

        $stockItem = $product->getExtensionAttributes()->getStockItem();
        //Use getData() method instead of getNotifyStockQty() to get non-processed value.
        $notifyStockQty = $stockItem->getData(StockItemConfigurationInterface::NOTIFY_STOCK_QTY);
        $assignedSources[] = [
            SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
            StockItemConfigurationInterface::NOTIFY_STOCK_QTY => $notifyStockQty,
            'notify_stock_qty_use_default' => $notifyStockQty === null ? 1 : $stockItem->getUseConfigNotifyStockQty(),
        ];
        try {
            $this->sourceItemsConfigurationProcessor->process($product->getSku(), $assignedSources);
        } catch (InputException $e) {
            $this->logger->error($e->getLogMessage());
        }

        return $result;
    }
}

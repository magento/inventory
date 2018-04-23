<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryCatalog\Model\DefaultSourceProvider;
use Magento\InventoryCatalog\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductTypeInterface;

/**
 * Save source relations (configuration) during product persistence via controller
 *
 * This needs to be handled in dedicated observer, because there is no pre-defined way of making several API calls for
 * Form submission handling
 */
class ProcessSourceItemConfigurationsObserver implements ObserverInterface
{
    /**
     * @var IsSourceItemsAllowedForProductTypeInterface
     */
    private $isSourceItemsAllowedForProductType;

    /**
     * @var SourceItemsConfigurationProcessor
     */
    private $sourceItemsConfigurationProcessor;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var DefaultSourceProvider
     */
    private $defaultSourceProvider;

    /**
     * @param IsSourceItemsAllowedForProductTypeInterface $isSourceItemsAllowedForProductType
     * @param SourceItemsConfigurationProcessor $sourceItemsConfigurationProcessor
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param DefaultSourceProvider $defaultSourceProvider
     */
    public function __construct(
        IsSourceItemsAllowedForProductTypeInterface $isSourceItemsAllowedForProductType,
        SourceItemsConfigurationProcessor $sourceItemsConfigurationProcessor,
        IsSingleSourceModeInterface $isSingleSourceMode,
        DefaultSourceProvider $defaultSourceProvider
    ) {
        $this->isSourceItemsAllowedForProductType = $isSourceItemsAllowedForProductType;
        $this->sourceItemsConfigurationProcessor = $sourceItemsConfigurationProcessor;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getEvent()->getProduct();
        if ($this->isSourceItemsAllowedForProductType->execute($product->getTypeId()) === false) {
            return;
        }

        /** @var Save $controller */
        $controller = $observer->getEvent()->getController();

        if ($this->isSingleSourceMode->execute() === true) {
            $productData = $controller->getRequest()->getParam('product', []);

            $this->processSourceItemConfigurationInSingleSourceMode($productData, $product->getSku());
        } else {
            $sources = $controller->getRequest()->getParam('sources', []);

            $this->processSourceItemsConfigurationInMultiSourceMode($sources, $product->getSku());
        }
    }

    /**
     * @param array $productData
     * @param string $sku
     *
     * @return void
     */
    private function processSourceItemConfigurationInSingleSourceMode(array $productData, string $sku): void
    {
        if (isset($productData[AdvancedInventory::STOCK_DATA_FIELDS])) {
            $stockData = $productData[AdvancedInventory::STOCK_DATA_FIELDS];

            $sourceItemData = [
                'source_code' => $this->defaultSourceProvider->getCode(),
                'notify_stock_qty_use_default' => $stockData['use_config_notify_stock_qty'],
                'notify_stock_qty' => $stockData['notify_stock_qty'],
            ];

            $this->sourceItemsConfigurationProcessor->process($sku, [$sourceItemData]);
        }
    }

    /**
     * @param array $sources
     * @param string $sku
     *
     * @return void
     */
    private function processSourceItemsConfigurationInMultiSourceMode(array $sources, string $sku): void
    {
        $assignedSources = isset($sources['assigned_sources']) && is_array($sources['assigned_sources'])
            ? $sources['assigned_sources']
            : [];

        $this->sourceItemsConfigurationProcessor->process($sku, $assignedSources);
    }
}

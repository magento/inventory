<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\InputException;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;

/**
 * Save source product relations during product persistence via controller
 *
 * This needs to be handled in dedicated observer, because there is no pre-defined way of making several API calls for
 * Form submission handling
 */
class ProcessSourceItemsObserver implements ObserverInterface
{
    /**
     * @var SourceItemsProcessor
     */
    private $sourceItemsProcessor;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param SourceItemsProcessor $sourceItemsProcessor
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        SourceItemsProcessor $sourceItemsProcessor,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->sourceItemsProcessor = $sourceItemsProcessor;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Process source items during product saving via controller
     *
     * @param EventObserver $observer
     * @return void
     * @throws InputException (thrown by SourceItemsProcessor)
     */
    public function execute(EventObserver $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getEvent()->getProduct();

        /** @var Save $controller */
        $controller = $observer->getEvent()->getController();

        $sources = $controller->getRequest()->getParam('sources', []);
        $assignedSources = $this->retrieveAssignedSources($sources);
        $assignedSources = $this->extendWithDefaultSource($assignedSources, $product);

        $this->sourceItemsProcessor->process(
            $product->getSku(),
            $assignedSources
        );
    }

    /**
     * @param array $sources
     * @return array
     */
    private function retrieveAssignedSources(array $sources): array
    {
        $assignedSources = isset($sources['assigned_sources']) && is_array($sources['assigned_sources'])
            ? $sources['assigned_sources']
            : [];

        return $assignedSources;
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    private function retrieveStockDataFromProduct(ProductInterface $product): array
    {
        $extendedAttributes = $product->getExtensionAttributes();
        if ($extendedAttributes) {
            $stockItem = $extendedAttributes->getStockItem();
            if ($stockItem) {
                return [
                    'quantity' => (float)$stockItem->getQty(),
                    'status' => (int)$stockItem->getIsInStock(),
                ];
            }
        }

        if ($product->hasData('stock_data')) {
            $stockData = $product->getData('stock_data');
            return [
                'quantity' => (float)$stockData['qty'],
                'status' => (int)$stockData['is_in_stock'],
            ];
        }

        return [];
    }

    /**
     * @param array $assignedSources
     * @param ProductInterface $product
     * @return array
     */
    private function extendWithDefaultSource(array $assignedSources, ProductInterface $product): array
    {
        $defaultSourceId = $this->defaultSourceProvider->getId();

        foreach ($assignedSources as $key => $assignedSource) {
            if ((int)$assignedSource['source_id'] === $defaultSourceId) {
                $assignedSource = array_merge(
                    $assignedSource,
                    $this->retrieveStockDataFromProduct($product)
                );
                $assignedSources[$key] = $assignedSource;
                return $assignedSources;
            }
        }

        $assignedSources[] = array_merge(
            ['source_id' => $defaultSourceId],
            $this->retrieveStockDataFromProduct($product)
        );

        return $assignedSources;
    }
}

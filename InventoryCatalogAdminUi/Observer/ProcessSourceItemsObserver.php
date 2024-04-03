<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

/**
 * Save source product relations during product persistence via controller
 *
 * This needs to be handled in dedicated observer, because there is no pre-defined way of making several API calls for
 * Form submission handling
 */
class ProcessSourceItemsObserver implements ObserverInterface
{
    /**
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param SourceItemsProcessorInterface $sourceItemsProcessor
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        private IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        private SourceItemsProcessorInterface $sourceItemsProcessor,
        private IsSingleSourceModeInterface $isSingleSourceMode,
        private DefaultSourceProviderInterface $defaultSourceProvider,
        private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private SourceItemRepositoryInterface $sourceItemRepository,
        private StockRegistryInterface $stockRegistry
    ) {
    }

    /**
     * Process source items during product saving via controller.
     *
     * @param EventObserver $observer
     * @return void
     * @throws InputException
     */
    public function execute(EventObserver $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getEvent()->getProduct();
        if ($this->isSourceItemManagementAllowedForProductType->execute($product->getTypeId()) === false) {
            return;
        }
        /** @var Save $controller */
        $controller = $observer->getEvent()->getController();
        $productData = $controller->getRequest()->getParam('product', []);

        if (!$this->isSingleSourceMode->execute()) {
            $sources = $controller->getRequest()->getParam('sources', []);
            $assignedSources =
                isset($sources['assigned_sources'])
                && is_array($sources['assigned_sources'])
                    ? $this->prepareAssignedSources($sources['assigned_sources'])
                    : [];
            $this->sourceItemsProcessor->execute((string)$productData['sku'], $assignedSources);
        } else {
            /** @var StockItemInterface $stockItem */
            $stockItem = $product->getExtensionAttributes()?->getStockItem()
                ?? $this->stockRegistry->getStockItem($product->getId());
            $defaultSourceData = [
                SourceItemInterface::SKU => $product->getSku(),
                SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
                SourceItemInterface::QUANTITY => $stockItem->getQty(),
                SourceItemInterface::STATUS => $stockItem->getIsInStock(),
            ];
            $sourceItems = $this->getSourceItemsWithoutDefault($product->getSku());
            $sourceItems[] = $defaultSourceData;
            $this->sourceItemsProcessor->execute((string)$product->getSku(), $sourceItems);
        }
    }

    /**
     * Get Source Items Data without Default Source by SKU
     *
     * @param string $sku
     * @return array
     */
    private function getSourceItemsWithoutDefault(string $sku): array
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $this->defaultSourceProvider->getCode(), 'neq')
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        $sourceItemData = [];
        if ($sourceItems) {
            foreach ($sourceItems as $sourceItem) {
                $sourceItemData[] = [
                    SourceItemInterface::SKU => $sourceItem->getSku(),
                    SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                    SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                    SourceItemInterface::STATUS => $sourceItem->getStatus(),
                ];
            }
        }
        return $sourceItemData;
    }

    /**
     * Convert built-in UI component property qty into quantity and source_status into status
     *
     * @param array $assignedSources
     * @return array
     */
    private function prepareAssignedSources(array $assignedSources): array
    {
        foreach ($assignedSources as $key => $source) {
            if (!key_exists('quantity', $source) && isset($source['qty'])) {
                $source['quantity'] = (int) $source['qty'];
                $assignedSources[$key] = $source;
            }
            if (!key_exists('status', $source) && isset($source['source_status'])) {
                $source['status'] = (int) $source['source_status'];
                $assignedSources[$key] = $source;
            }
        }
        return $assignedSources;
    }
}

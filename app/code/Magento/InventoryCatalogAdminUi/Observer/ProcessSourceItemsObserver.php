<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\DeleteSourceItemsBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
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
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var SourceItemsProcessor
     */
    private $sourceItemsProcessor;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var DeleteSourceItemsBySkusInterface
     */
    private $deleteSourceItems;

    /**
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param SourceItemsProcessor $sourceItemsProcessor
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param DeleteSourceItemsBySkusInterface $deleteSourceItems
     */
    public function __construct(
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        SourceItemsProcessor $sourceItemsProcessor,
        IsSingleSourceModeInterface $isSingleSourceMode,
        DefaultSourceProviderInterface $defaultSourceProvider,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SourceItemRepositoryInterface $sourceItemRepository,
        DeleteSourceItemsBySkusInterface $deleteSourceItems
    ) {
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->sourceItemsProcessor = $sourceItemsProcessor;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->deleteSourceItems = $deleteSourceItems;
    }

    /**
     * Process source items during product saving via controller
     *
     * @param EventObserver $observer
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function execute(EventObserver $observer)
    {
        /** @var ProductInterface $product */
        $product = $observer->getEvent()->getProduct();
        if ($this->areSourceItemsShouldBeDeleted($product)) {
            $this->deleteSourceItems->execute([(string)$product->getOrigData('sku')]);
        }
        if ($this->isSourceItemManagementAllowedForProductType->execute($product->getTypeId()) === false) {
            return;
        }
        $controller = $observer->getEvent()->getController();
        $productData = $controller->getRequest()->getParam('product', []);
        $singleSourceData = $productData['quantity_and_stock_status'] ?? [];

        if (!$this->isSingleSourceMode->execute()) {
            $sources = $controller->getRequest()->getParam('sources', []);
            $assignedSources = $sources['assigned_sources'] ?? [];
            $this->sourceItemsProcessor->process($productData['sku'], $assignedSources);
        } elseif (!empty($singleSourceData)) {
            /** @var StockItemInterface $stockItem */
            $stockItem = $product->getExtensionAttributes()->getStockItem();
            $qty = $singleSourceData['qty'] ?? (empty($stockItem) ? 0 : $stockItem->getQty());
            $isInStock = $singleSourceData['is_in_stock'] ?? (empty($stockItem) ? 1 : (int)$stockItem->getIsInStock());
            $defaultSourceData = [
                SourceItemInterface::SKU => $productData['sku'],
                SourceItemInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
                SourceItemInterface::QUANTITY => $qty,
                SourceItemInterface::STATUS => $isInStock,
            ];
            $sourceItems = $this->getSourceItemsWithoutDefault($productData['sku']);
            $sourceItems[] = $defaultSourceData;
            $this->sourceItemsProcessor->process($productData['sku'], $sourceItems);
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
     * Verify, if source items should be deleted for given product.
     *
     * @param ProductInterface $product
     * @return bool
     */
    private function areSourceItemsShouldBeDeleted(ProductInterface $product): bool
    {
        return !$this->isSingleSourceMode->execute()
            && (
                $product->getOrigData('type_id') !== $product->getTypeId()
                || $product->getOrigData('sku') !== $product->getSku()
            );
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model\Inventory;

use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Inventory\Model\ResourceModel\Source\CollectionFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;

/**
 * @inheritDoc
 */
class ChangeParentProductStockStatus
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var Configurable
     */
    private Configurable $configurableType;

    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    private SourceItemRepositoryInterface $sourceItemRepository;

    /**
     * @var StockItemRepositoryInterface
     */
    private StockItemRepositoryInterface $stockItemRepository;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private GetSkusByProductIdsInterface $getSkusByProductIds;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @param RequestInterface $request
     * @param Configurable $configurableType
     */
    public function __construct(
        RequestInterface $request,
        Configurable $configurableType,
        StockRegistryInterface $stockRegistry,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository,
        StockItemRepositoryInterface $stockItemRepository,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        CollectionFactory $collectionFactory
    ) {
        $this->request = $request;
        $this->configurableType = $configurableType;
        $this->stockRegistry = $stockRegistry;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->stockItemRepository = $stockItemRepository;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute($childproductId): void
    {
        $parentIds = $this->configurableType->getParentIdsByChild($childproductId);
        foreach (array_unique($parentIds) as $productId) {
            $this->processStockForParent((int)$childproductId, (int)$productId);
        }
    }

    /**
     * Update stock status of configurable product based on children products stock status
     *
     * @param int $childproductId
     * @param int $productId
     * @return void
     */
    private function processStockForParent(int $childproductId, int $productId): void
    {
        $childrenIsInStock = false;

        if ($sources = $this->request->getParam('sources')) {
            if ($currentsourceItems = $sources['assigned_sources']) {
                foreach ($currentsourceItems as $childItem) {
                    if ($childItem['status'] && $childItem['quantity'] > 0 && $childItem['source_status']) {
                        $childrenIsInStock = true;
                        break;
                    }
                }
            }
        }

        if (!$childrenIsInStock) {
            $sourceCodes = $this->collectionFactory->create()
                ->addFieldToFilter(SourceInterface::ENABLED, 1)
                ->addFieldToSelect('source_code')
                ->getColumnValues('source_code');
            $childrenIds = $this->configurableType->getChildrenIds($productId);
            $childrenSkus = $this->getSkusByProductIds->execute($childrenIds[0]);

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCodes, 'in')
                ->addFilter(SourceItemInterface::SKU, $childrenSkus, 'in')
                ->addFilter(SourceItemInterface::SKU, $childrenSkus[$childproductId], 'neq')
                ->addFilter(SourceItemInterface::STATUS, 1)
                ->create();

            $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
            foreach ($sourceItems as $childItem) {
                if ($childItem->getStatus()) {
                    $childrenIsInStock = true;
                    break;
                }
            }
        }

        $parentStockItem = $this->stockRegistry->getStockItem($productId);
        $parentStockItem->setIsInStock($childrenIsInStock);
        $parentStockItem->setStockStatusChangedAuto(1);
        $this->stockItemRepository->save($parentStockItem);
    }
}

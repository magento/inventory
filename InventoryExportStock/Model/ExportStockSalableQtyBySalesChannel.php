<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStock\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\InventoryExportStockApi\Api\Data\ExportStockSalableQtySearchResultInterface;
use Magento\InventoryExportStockApi\Api\Data\ExportStockSalableQtySearchResultInterfaceFactory;
use Magento\InventoryExportStockApi\Api\ExportStockSalableQtyBySalesChannelInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;

/**
 * Class ExportStockSalableQty provides product stock information by search criteria
 */
class ExportStockSalableQtyBySalesChannel implements ExportStockSalableQtyBySalesChannelInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ExportStockSalableQtySearchResultInterfaceFactory
     */
    private $exportStockSalableQtySearchResultFactory;

    /**
     * @var PreciseExportStockProcessor
     */
    private $preciseExportStockProcessor;

    /**
     * @var GetStockBySalesChannelInterface
     */
    private $getStockBySalesChannel;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ExportStockSalableQtySearchResultInterfaceFactory $exportStockSalableQtySearchResultFactory
     * @param PreciseExportStockProcessor $preciseExportStockProcessor
     * @param GetStockBySalesChannelInterface $getStockBySalesChannel
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ExportStockSalableQtySearchResultInterfaceFactory $exportStockSalableQtySearchResultFactory,
        PreciseExportStockProcessor $preciseExportStockProcessor,
        GetStockBySalesChannelInterface $getStockBySalesChannel,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->exportStockSalableQtySearchResultFactory = $exportStockSalableQtySearchResultFactory;
        $this->preciseExportStockProcessor = $preciseExportStockProcessor;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function execute(
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): ExportStockSalableQtySearchResultInterface {
        $stock = $this->getStockBySalesChannel->execute($salesChannel);
        // Build a fresh SearchCriteria with original filters + our inventory filter
        $builder = $this->searchCriteriaBuilder;
        $builder->setCurrentPage($searchCriteria->getCurrentPage());
        $builder->setPageSize($searchCriteria->getPageSize());
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            $builder->addFilters($filterGroup->getFilters());
        }
        foreach ($searchCriteria->getSortOrders() ?: [] as $sortOrder) {
            $builder->addSortOrder($sortOrder);
        }
        // Add stock filter that our custom processor intercepts
        $builder->addFilter('inventory_stock_id', $stock->getStockId(), 'eq');
        $searchCriteriaWithInventory = $builder->create();

        $productSearchResult = $this->getProducts($searchCriteriaWithInventory);
        $items = $this->preciseExportStockProcessor->execute($productSearchResult->getItems(), $stock->getStockId());

        /** @var ExportStockSalableQtySearchResultInterface $searchResult */
        $searchResult = $this->exportStockSalableQtySearchResultFactory->create();
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($items);
        // Single-pass: total count comes from filtered collection getSize()
        $searchResult->setTotalCount($productSearchResult->getTotalCount());

        return $searchResult;
    }

    /**
     * Provides product search result by search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SearchResultsInterface
     */
    private function getProducts(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        return $this->productRepository->getList($searchCriteria);
    }
}

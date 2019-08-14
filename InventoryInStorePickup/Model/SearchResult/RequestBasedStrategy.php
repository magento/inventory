<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchResult;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchResult\ExtractStrategyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Work with default data set.
 * This assume that data set did not filtered by assignment to Stock.
 */
class RequestBasedStrategy implements ExtractStrategyInterface
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @param StockResolverInterface $stockResolver
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        GetStockSourceLinksInterface $getStockSourceLinks
    ) {
        $this->stockResolver = $stockResolver;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->getStockSourceLinks = $getStockSourceLinks;
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     */
    public function getSources(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): array {
        $stockId = $this->getStockId($searchRequest);
        $sourceCodes = [];

        foreach ($sourcesSearchResult->getItems() as $item) {
            $sourceCodes[] = $item->getSourceCode();
        }

        $sourceCodes = $this->getSourceCodesAssignedToStock($stockId, $sourceCodes);
        $sources = [];

        foreach ($sourcesSearchResult->getItems() as $source) {
            if (in_array($source->getSourceCode(), $sourceCodes)) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    /**
     * Get list of Source Codes assigned to the Stock.
     *
     * @param int $stockId
     * @param string[] $sourceCodes
     *
     * @return string[]
     */
    private function getSourceCodesAssignedToStock(int $stockId, array $sourceCodes): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaStockSource = $searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->addFilter(StockSourceLinkInterface::SOURCE_CODE, $sourceCodes, 'in')
            ->create();

        $searchResult = $this->getStockSourceLinks->execute($searchCriteriaStockSource);

        $codes = [];
        foreach ($searchResult->getItems() as $item) {
            $codes[] = $item->getSourceCode();
        }

        return $codes;
    }

    /**
     * Get Stock Id from Search Request.
     *
     * @param SearchRequestInterface $searchRequest
     *
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStockId(SearchRequestInterface $searchRequest): int
    {
        $scopeType = $searchRequest->getScopeType();
        $scopeCode = $searchRequest->getScopeCode();

        $stock = $this->stockResolver->execute($scopeType, $scopeCode);

        return $stock->getStockId();
    }
}

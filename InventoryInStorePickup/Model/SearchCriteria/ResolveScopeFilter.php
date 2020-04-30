<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\ResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Add filter by Source Codes which are related to Requested Scope.
 *
 * In case of Distance Filter present in Search Request, the filter will not be added.
 */
class ResolveScopeFilter implements ResolverInterface
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
     *
     * @throws NoSuchEntityException
     */
    public function resolve(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ): void {
        $stockId = $this->getStockId($searchRequest);
        $sourceCodes = $this->getSourceCodesAssignedToStock($stockId);

        $searchCriteriaBuilder->addFilter(SourceInterface::SOURCE_CODE, implode(',', $sourceCodes), 'in');
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

    /**
     * Get list of Source Codes assigned to the Stock.
     *
     * @param int $stockId
     *
     * @return string[]
     */
    private function getSourceCodesAssignedToStock(int $stockId): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaStockSource = $searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->create();

        $searchResult = $this->getStockSourceLinks->execute($searchCriteriaStockSource);

        $codes = [];
        foreach ($searchResult->getItems() as $item) {
            $codes[] = $item->getSourceCode();
        }

        return $codes;
    }
}

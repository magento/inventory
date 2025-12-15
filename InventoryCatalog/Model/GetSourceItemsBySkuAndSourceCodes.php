<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

class GetSourceItemsBySkuAndSourceCodes
{
    /** @var SearchCriteriaBuilder  */
    private $searchCriteriaBuilder;

    /** @var SourceItemRepositoryInterface  */
    private $sourceItemRepository;

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceItemRepositoryInterface $sourceItemRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceItemRepositoryInterface $sourceItemRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceItemRepository = $sourceItemRepository;
    }

    /**
     * Fetches source items by SKU and source codes using search criteria to filter and return matching items.
     *
     * @param string $sku
     * @param array $sourceCodes
     * @return SourceItemInterface[]
     */
    public function execute(string $sku, array $sourceCodes)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, [$sourceCodes], 'in')
            ->create();

        return $this->sourceItemRepository->getList($searchCriteria)->getItems();
    }
}

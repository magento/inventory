<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductAdminUi\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class GetQuantityInformationPerSource
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * Gets quantity and status information per source for a given SKU by fetching source and source item details.
     *
     * @param string $sku
     *
     * @return array
     */
    public function execute(string $sku): array
    {
        $sourceItemsInformation = [];

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter(SourceItemInterface::SKU, $sku)->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        foreach ($sourceItems as $sourceItem) {
            $source = $this->sourceRepository->get($sourceItem->getSourceCode());

            $sourceItemsInformation[] = [
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                'quantity_per_source' => $sourceItem->getQuantity(),
                'source' => $source->getName(),
                SourceItemInterface::STATUS => $sourceItem->getStatus(),
            ];
        }

        return $sourceItemsInformation;
    }
}

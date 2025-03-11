<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Load product source items data by skus.
 */
class GetQuantityInformationPerSourceBySkus
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
     * Get products source items by skus.
     *
     * @param array $skus
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(array $skus): array
    {
        $sourceItemsInformation = [];

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter(SourceItemInterface::SKU, $skus, 'in')->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $sources = $this->getSources($sourceItems);

        foreach ($sourceItems as $sourceItem) {
            $source = $sources[$sourceItem->getSourceCode()] ?? null;
            if (!$source) {
                throw new NoSuchEntityException(
                    __('Source with code "%value" does not exist.', ['value' => $sourceItem->getSourceCode()])
                );
            }
            $sourceItemsInformation[$sourceItem->getSku()][] = [
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                'source_name' => $source->getName(),
                SourceItemInterface::STATUS => $sourceItem->getStatus(),
            ];
        }

        return $sourceItemsInformation;
    }

    /**
     * Returns sources by source items.
     *
     * @param SourceItemInterface[] $sourceItems
     * @return SourceInterface[]
     */
    private function getSources(array $sourceItems): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $sourceCodes = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCodes[$sourceItem->getSourceCode()] = true;
        }
        $sourceCodes = array_keys($sourceCodes);
        $searchCriteria = $searchCriteriaBuilder->addFilter(SourceInterface::SOURCE_CODE, $sourceCodes, 'in')->create();
        $sources = [];
        foreach ($this->sourceRepository->getList($searchCriteria)->getItems() as $source) {
            $sources[$source->getSourceCode()] = $source;
        }
        return $sources;
    }
}

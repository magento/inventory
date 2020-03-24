<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
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

        foreach ($sourceItems as $sourceItem) {
            $source = $this->sourceRepository->get($sourceItem->getSourceCode());
            $sourceItemsInformation[$sourceItem['sku']][] = [
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                'source_name' => $source->getName(),
                SourceItemInterface::STATUS => $sourceItem->getStatus(),
            ];
        }

        return $sourceItemsInformation;
    }
}

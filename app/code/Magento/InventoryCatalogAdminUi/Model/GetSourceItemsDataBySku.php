<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Model\IsSourceAllowedForUserInterface;

/**
 * Get source items for given product model.
 */
class GetSourceItemsDataBySku
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var IsSourceAllowedForUserInterface|null
     */
    private $isSourceAllowedForUser;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param IsSourceAllowedForUserInterface|null $isSourceAllowedForUser
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        IsSourceAllowedForUserInterface $isSourceAllowedForUser = null
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->isSourceAllowedForUser = $isSourceAllowedForUser ?: ObjectManager::getInstance()
            ->get(IsSourceAllowedForUserInterface::class);
    }

    /**
     * Get source items data for given product sku.
     *
     * @param string $sku
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(string $sku): array
    {
        $sourceItemsData = [];

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $sourcesCache = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCode = $sourceItem->getSourceCode();
            if (!isset($sourcesCache[$sourceCode])) {
                $sourcesCache[$sourceCode] = [
                    'source' => $this->sourceRepository->get($sourceCode),
                    'allowed' => $this->isSourceAllowedForUser->execute($sourceCode),
                ];
            }

            if ($sourcesCache[$sourceCode]['allowed']) {
                $source = $sourcesCache[$sourceCode]['source'];
                $sourceItemsData[] = [
                    SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                    SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                    SourceItemInterface::STATUS => $sourceItem->getStatus(),
                    SourceInterface::NAME => $source->getName(),
                    'source_status' => $source->isEnabled(),
                ];
            }
        }

        return $sourceItemsData;
    }
}

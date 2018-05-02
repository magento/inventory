<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCreditMemo\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;

class GetSourceItem
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->sourceItemRepository = $sourceItemRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     *
     * @return SourceItemInterface
     */
    public function execute(string $sku, string $sourceCode): SourceItemInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $sourceItem = reset($sourceItems);

        return $sourceItem;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Model\Indexer;

use Magento\CatalogInventory\Model\ResourceModel\StockStatusFilterInterface;
use Magento\Framework\DB\Select;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Filter products by stock status
 */
class FilterProductByStock
{
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var StockStatusFilterInterface
     */
    private $stockStatusFilter;

    /**
     * @var SelectModifierInterface[]
     */
    private $selectModifiersPool;

    /**
     * @param StoreRepositoryInterface $storeRepository
     * @param StockStatusFilterInterface $stockStatusFilter
     * @param SelectModifierInterface[] $selectModifiersPool
     */
    public function __construct(
        StoreRepositoryInterface $storeRepository,
        StockStatusFilterInterface $stockStatusFilter,
        array $selectModifiersPool = []
    ) {
        $this->storeRepository = $storeRepository;
        $this->stockStatusFilter = $stockStatusFilter;
        $this->selectModifiersPool = $selectModifiersPool;
    }

    /**
     * Return filtered product by stock status for product indexer
     *
     * @param Select $select
     * @param int $storeId
     * @return Select
     */
    public function execute(Select $select, int $storeId): Select
    {
        $store = $this->storeRepository->getById($storeId);
        $this->stockStatusFilter->execute(
            $select,
            'e',
            StockStatusFilterInterface::TABLE_ALIAS,
            (int) $store->getWebsiteId()
        );
        $this->applySelectModifiers($select, $storeId);

        return $select;
    }

    /**
     * Applying filters to select via select modifiers
     *
     * @param Select $select
     * @param int $storeId
     * @return void
     */
    private function applySelectModifiers(Select $select, int $storeId): void
    {
        foreach ($this->selectModifiersPool as $selectModifier) {
            $selectModifier->modify($select, $storeId);
        }
    }
}

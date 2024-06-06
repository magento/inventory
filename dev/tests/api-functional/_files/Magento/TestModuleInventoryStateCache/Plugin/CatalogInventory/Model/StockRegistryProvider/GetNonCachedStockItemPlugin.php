<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestModuleInventoryStateCache\Plugin\CatalogInventory\Model\StockRegistryProvider;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;

/**
 * Get non cached stock item for integration tests plugin.
 *
 * Using StockRegistryStorage as local cache for getting stock items leads to not adding stock items to DB
 * in case of multiple test cases running in a row with the same products.
 */
class GetNonCachedStockItemPlugin
{
    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockItemInterfaceFactory
     */
    private $stockItemFactory;

    /**
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemInterfaceFactory $stockItemFactory
     */
    public function __construct(
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        StockItemRepositoryInterface $stockItemRepository,
        StockItemInterfaceFactory $stockItemFactory
    ) {
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->stockItemRepository = $stockItemRepository;
        $this->stockItemFactory = $stockItemFactory;
    }

    /**
     * Get non cached stock items for product.
     *
     * @param StockRegistryProviderInterface $subject
     * @param \Closure $proceed
     * @param int|null $productId
     * @param int|null $stockId
     * @return StockItemInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetStockItem(
        StockRegistryProviderInterface $subject,
        \Closure $proceed,
        ?int $productId,
        ?int $stockId
    ): StockItemInterface {
        $criteria = $this->stockItemCriteriaFactory->create();
        $criteria->setProductsFilter($productId);
        $collection = $this->stockItemRepository->getList($criteria);
        $stockItem = current($collection->getItems());
        if (!$stockItem || !$stockItem->getItemId()) {
            $stockItem = $this->stockItemFactory->create();
        }

        return $stockItem;
    }
}

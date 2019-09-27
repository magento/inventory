<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSearch\Model\Indexer\Fulltext\Action\DataProvider;

use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;

/**
 * Filter out of stock products for indexData
 */
class FilterOutOfStockProducts
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var StockStatusCriteriaInterfaceFactory
     */
    private $statusCriteriaInterfaceFactory;

    /**
     * @var StockStatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @param StockStatusCriteriaInterfaceFactory $statusCriteriaInterfaceFactory
     * @param StockStatusRepositoryInterface $statusRepository
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        StockStatusCriteriaInterfaceFactory $statusCriteriaInterfaceFactory,
        StockStatusRepositoryInterface $statusRepository
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->statusCriteriaInterfaceFactory = $statusCriteriaInterfaceFactory;
        $this->statusRepository = $statusRepository;
    }

    /**
     * Filter out of stock products for indexData for specific store.
     *
     * @param array $indexData
     * @param int $storeId
     * @return array
     */
    public function execute(array $indexData, int $storeId): array
    {
        if (!$this->stockConfiguration()->isShowOutOfStock($storeId)) {
            $productIds = array_keys($indexData);
            $stockStatusCriteria = $this->statusCriteriaInterfaceFactory->create();
            $stockStatusCriteria->setProductsFilter($productIds);
            $stockStatusCollection = $this->statusRepository->getList($stockStatusCriteria);
            $stockStatuses = $stockStatusCollection->getItems();
            $stockStatuses = array_filter(
                $stockStatuses,
                function (StockStatusInterface $stockStatus) {
                    return StockStatusInterface::STATUS_IN_STOCK == $stockStatus->getStockStatus();
                }
            );
            $indexData = array_intersect_key($indexData, $stockStatuses);
        }

        return $indexData;
    }
}

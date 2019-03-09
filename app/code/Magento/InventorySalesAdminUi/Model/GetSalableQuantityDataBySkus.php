<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetSalableQuantityData;

/**
 * Get salable quantity data by SKUs
 */
class GetSalableQuantityDataBySkus
{
    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetSalableQuantityData
     */
    private $getSalableQuantityData;

    /**
     * GetSalableQuantityDataBySkus constructor.
     * @param StockRepositoryInterface $stockRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetSalableQuantityData $getSalableQuantityData
     */
    public function __construct(
        StockRepositoryInterface $stockRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetSalableQuantityData $getSalableQuantityData
    ) {
        $this->stockRepository = $stockRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getSalableQuantityData = $getSalableQuantityData;
    }

    /**
     * @param array $skus
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function execute(array $skus): array
    {
        $stocks = $this->stockRepository->getList($this->searchCriteriaBuilder->create())->getItems();

        $salableQuantity = [];
        foreach ($stocks as $stock) {
            $salableQuantityPerStock = $this->getSalableQuantityData->execute((int) $stock->getStockId(), $skus);

            foreach ($salableQuantityPerStock as $salableItem) {
                $salableQuantity[$salableItem['sku']][] = [
                    'stock_name' => $stock->getName(),
                    'qty' => (float)$salableItem['salable_quantity'],
                    'manage_stock' => (bool) $salableItem['is_salable']
                ];
            }
        }

        return $salableQuantity;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Plugin\CatalogSearch\Model\Indexer;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogSearch\Model\Indexer\Fulltext\Action\GetSearchableProductsSelect;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogSearch\Model\Indexer\FilterProductByStock;

/**
 * Filter composite products by enabled child product stock status.
 */
class ChildProductFilterByInventoryStockPlugin
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var FilterProductByStock
     */
    private $filterProductByStock;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @param FilterProductByStock $filterProductByStock
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        FilterProductByStock $filterProductByStock
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->filterProductByStock = $filterProductByStock;
    }

    /**
     * Filter out of stock options for composite products.
     *
     * @param GetSearchableProductsSelect $subject
     * @param Select $result
     * @param int $storeId
     * @return Select
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        GetSearchableProductsSelect $subject,
        Select $result,
        int $storeId
    ): Select
    {
        if ($this->stockConfiguration->isShowOutOfStock($storeId) || empty($result)) {
            return $result;
        }
        return $this->filterProductByStock->execute($result, $storeId);
    }
}

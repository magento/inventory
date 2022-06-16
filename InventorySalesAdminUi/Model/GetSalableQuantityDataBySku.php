<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetStockNamesByIds;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;

/**
 * Get salable quantity data of product by sku
 */
class GetSalableQuantityDataBySku
{
    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var GetAssignedStockIdsBySku
     */
    private $getAssignedStockIdsBySku;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var GetStockNamesByIds
     */
    private $getStockNamesByIds;

    /**
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param StockRepositoryInterface $stockRepository
     * @param GetAssignedStockIdsBySku $getAssignedStockIdsBySku
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetStockNamesByIds $getStockNamesByIds
     */
    public function __construct(
        GetProductSalableQtyInterface $getProductSalableQty,
        StockRepositoryInterface $stockRepository,
        GetAssignedStockIdsBySku $getAssignedStockIdsBySku,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        DefaultStockProviderInterface $defaultStockProvider,
        GetStockNamesByIds $getStockNamesByIds
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockRepository = $stockRepository;
        $this->getAssignedStockIdsBySku = $getAssignedStockIdsBySku;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getStockNamesByIds = $getStockNamesByIds;
    }

    /**
     * Get salable quantity of product by sku
     *
     * @param string $sku
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(string $sku): array
    {
        // It is a global configuration and the value is the same for all assigned stocks.
        $isManageStock = $this->getStockItemConfiguration
            ->execute($sku, $this->defaultStockProvider->getId())
            ->isManageStock();

        $stockInfo = [];
        $stockIds = $this->getAssignedStockIdsBySku->execute($sku);
        $stockNames = $this->getStockNamesByIds->execute($stockIds);
        foreach ($stockIds as $stockId) {
            $stockId = (int) $stockId;
            $stockInfo[] = [
                'stock_id' => $stockId,
                'stock_name' => $stockNames[$stockId],
                'qty' => $isManageStock ? $this->getProductSalableQty->execute($sku, $stockId) : null,
                'manage_stock' => $isManageStock,
            ];
        }

        return $stockInfo;
    }
}

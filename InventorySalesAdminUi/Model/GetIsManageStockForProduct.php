<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Class to determine if a product has Manage Stock option on or off
 */
class GetIsManageStockForProduct
{
    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param StockRepositoryInterface $stockRepository
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     */
    public function __construct(
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        StockRepositoryInterface $stockRepository,
        GetStockItemConfigurationInterface $getStockItemConfiguration
    ) {
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->stockRepository = $stockRepository;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
    }

    /**
     * Determine if a product has Manage Stock option on or off
     *
     * @param string $sku
     * @param string $websiteCode
     * @return bool|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(string $sku, string $websiteCode): ?bool
    {
        $isManageStock = null;
        $stockIds = $this->getProductStockIds($sku);
        foreach ($stockIds as $stockId) {
            $stock = $this->stockRepository->get($stockId);
            $salesChannels = $stock->getExtensionAttributes()->getSalesChannels();
            foreach ($salesChannels as $salesChannel) {
                if ($salesChannel->getType() === SalesChannelInterface::TYPE_WEBSITE
                    && $salesChannel->getCode() === $websiteCode
                ) {
                    $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
                    $isManageStock = $stockItemConfiguration->isManageStock();
                }
            }
        }
        return $isManageStock;
    }

    /**
     * Return product stock ids
     *
     * @param string $sku
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws SkuIsNotAssignedToStockException
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getProductStockIds(string $sku): array
    {
        $stockIds = [];
        $stocksInfo =$this->getSalableQuantityDataBySku->execute($sku);
        foreach ($stocksInfo as $stockInfo) {
            $stockIds[] = (int)$stockInfo['stock_id'];
        }
        return $stockIds;
    }
}

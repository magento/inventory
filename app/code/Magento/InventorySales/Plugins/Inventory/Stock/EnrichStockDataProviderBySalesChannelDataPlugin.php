<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugins\Inventory\Stock;

use Magento\CatalogInventory\Model\Stock\StockRepository;
use Magento\Inventory\Ui\DataProvider\StockDataProvider;
use Magento\InventorySales\Model\GetAssignedSalesChannelsForStockInterface;

/**
 * Enriches StockDataProvider by Stock Channel information
 */
class EnrichStockDataProviderBySalesChannelDataPlugin
{
    /**
     * @var GetAssignedSalesChannelsForStockInterface
     */
    private $getAssignedSalesChannelsForStock;

    /**
     * @var StockRepository
     */
    private $stockRepository;

    /**
     * EnrichStockDataProviderBySaleChannelDataPlugin constructor.
     *
     * @param GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannelsForStock
     * @param StockRepository $stockRepository
     */
    public function __construct(
        GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannelsForStock,
        StockRepository $stockRepository
    ) {
        $this->getAssignedSalesChannelsForStock = $getAssignedSalesChannelsForStock;
        $this->stockRepository = $stockRepository;
    }

    /**
     * Enriches StockDataProvider by Stock Channel information
     *
     * @param StockDataProvider $subject
     * @param array $data
     *
     * @return array
     */
    public function afterGetData(StockDataProvider $subject, array $data): array
    {
        if ('inventory_stock_form_data_source' === $subject->getName()) {
            foreach ($data as &$stockData) {
                $salesChannelsData = $this->getSalesChannelsDataForStock($stockData['general']);
                if (count($salesChannelsData)) {
                    $stockData['sales_channels'] = $salesChannelsData;
                }
            }
            unset($stockData);
        } elseif ($data['totalRecords'] > 0) {
            foreach ($data['items'] as &$stockData) {
                $salesChannelsData = $this->getSalesChannelsDataForStock($stockData);
                if (count($salesChannelsData)) {
                    $stockData['sales_channels'] = $salesChannelsData;
                }
            }
            unset($stockData);
        }

        return $data;
    }

    /**
     * Retrieves Sale Channel information by Stock data
     *
     * @param array $stock
     *
     * @return array
     */
    private function getSalesChannelsDataForStock(array $stock): array
    {
        $salesChannelsData = [];

        foreach ($stock['extension_attributes'] as $salesChannels) {
            foreach ($salesChannels as $salesChannel) {
                $salesChannelsData[$salesChannel['type']][] = $salesChannel['code'];
            }
        }

        return $salesChannelsData;
    }
}

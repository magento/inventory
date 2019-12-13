<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Plugin\InventoryAdminUi\Ui\StockDataProvider;

use Magento\CatalogInventory\Model\Stock\StockRepository;
use Magento\Framework\App\ObjectManager;
use Magento\InventoryAdminUi\Ui\DataProvider\StockDataProvider;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;
use Magento\Store\Model\ResourceModel\Website\Collection;

/**
 * Customize stock form. Add sales channels data
 */
class SalesChannels
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
     * @var Collection
     */
    private $websiteCollection;

    /**
     * @param GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannelsForStock
     * @param StockRepository $stockRepository
     * @param Collection|null $websiteCollection
     */
    public function __construct(
        GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannelsForStock,
        StockRepository $stockRepository,
        Collection $websiteCollection = null
    ) {
        $this->getAssignedSalesChannelsForStock = $getAssignedSalesChannelsForStock;
        $this->stockRepository = $stockRepository;
        $this->websiteCollection = $websiteCollection ?: ObjectManager::getInstance()->get(Collection::class);
    }

    /**
     * Plugin to add sales channels to stock data provider.
     *
     * @param StockDataProvider $subject
     * @param array $data
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
        } elseif (isset($data['totalRecords']) && $data['totalRecords'] > 0) {
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
     * Get sales channels from extension attributes on stock data.
     *
     * @param array $stock
     * @return array
     */
    private function getSalesChannelsDataForStock(array $stock): array
    {
        $codes = [];
        $websites = $this->websiteCollection->getItems();
        foreach ($websites as $website) {
            $codes[] = $website->getCode();
        }
        $salesChannelsData = [];
        if (isset($stock['extension_attributes']) && isset($stock['extension_attributes']['sales_channels'])) {
            $salesChannels = $stock['extension_attributes']['sales_channels'];
            foreach ($salesChannels as $salesChannel) {
                if ($salesChannel[SalesChannelInterface::TYPE] === SalesChannelInterface::TYPE_WEBSITE
                    && !in_array($salesChannel[SalesChannelInterface::CODE], $codes)) {
                    continue;
                }
                $salesChannelsData[$salesChannel['type']][] = $salesChannel['code'];
            }
        }
        return $salesChannelsData;
    }
}

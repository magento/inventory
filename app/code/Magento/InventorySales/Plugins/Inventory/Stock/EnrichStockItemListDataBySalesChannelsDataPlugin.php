<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugins\Inventory\Stock;

use Magento\Framework\Exception\StateException;
use Magento\InventoryApi\Api\Data\StockSearchResultsInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySales\Model\GetAssignedSalesChannelsForStockInterface;
use Psr\Log\LoggerInterface;

/**
 * Enriches Stock Item List Data by Sale Channels Data on StockRepositoryInterface::getList()
 */
class EnrichStockItemListDataBySalesChannelsDataPlugin
{
    /**
     * @var GetAssignedSalesChannelsForStockInterface
     */
    private $getAssignedSalesChannelsForStock;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * LoadSalesChannelsOnGetListPlugin constructor.
     *
     * @param GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannelsForStock
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetAssignedSalesChannelsForStockInterface $getAssignedSalesChannelsForStock,
        LoggerInterface $logger
    ) {
        $this->getAssignedSalesChannelsForStock = $getAssignedSalesChannelsForStock;
        $this->logger = $logger;
    }

    /**
     * Enrich the given Stock Objects with the assigned sales channel entities
     *
     * @param StockRepositoryInterface $subject
     * @param StockSearchResultsInterface $stockSearchResults
     *
     * @return StockSearchResultsInterface
     *
     * @throws StateException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        StockRepositoryInterface $subject,
        StockSearchResultsInterface $stockSearchResults
    ): StockSearchResultsInterface {
        try {
            return $this->enrichStockItemListDataBySaleChannelsData($stockSearchResults);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new StateException(__('Could not load Sales Channels for Stock'), $e);
        }
    }

    /**
     * Enriches Stock Item List data by Sale Channels information
     *
     * @param StockSearchResultsInterface $stockSearchResults
     *
     * @return StockSearchResultsInterface
     */
    private function enrichStockItemListDataBySaleChannelsData(
        StockSearchResultsInterface $stockSearchResults
    ): StockSearchResultsInterface {
        $stocks = [];

        foreach ($stockSearchResults->getItems() as $stock) {
            $salesChannels = $this->getAssignedSalesChannelsForStock->execute((int)$stock->getStockId());

            $extensionAttributes = $stock->getExtensionAttributes();
            $extensionAttributes->setSalesChannels($salesChannels);
            $stock->setExtensionAttributes($extensionAttributes);
            $stocks[] = $stock;
        }

        $stockSearchResults->setItems($stocks);

        return $stockSearchResults;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugins\Inventory\Stock;

use Magento\Framework\Exception\StateException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySales\Model\GetAssignedSalesChannelsForStockInterface;
use Psr\Log\LoggerInterface;

/**
 *  Enriches Stock Data by Sale Channels Data on StockRepositoryInterface::get()
 */
class EnrichStockItemDataBySalesChannelsDataPlugin
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
     * EnrichStockItemDataBySaleChannelsDataPlugin constructor.
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
     * Enrich the given Stock Object with the assigned sales channel entities
     *
     * @param StockRepositoryInterface $subject
     * @param StockInterface $stock
     *
     * @return StockInterface
     *
     * @throws StateException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(StockRepositoryInterface $subject, StockInterface $stock): StockInterface
    {
        try {
            return $this->enrichStockItemDataBySaleChannelsData($stock);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new StateException(__('Could not load Sales Channels for Stock'), $e);
        }
    }

    /**
     * Enriches Stock Item data by Sale Channels information
     *
     * @param StockInterface $stock
     *
     * @return StockInterface
     */
    private function enrichStockItemDataBySaleChannelsData(StockInterface $stock): StockInterface
    {
        $salesChannels = $this->getAssignedSalesChannelsForStock->execute((int)$stock->getStockId());

        $extensionAttributes = $stock->getExtensionAttributes();
        $extensionAttributes->setSalesChannels($salesChannels);
        $stock->setExtensionAttributes($extensionAttributes);

        return $stock;
    }
}

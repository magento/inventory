<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;

/**
 * @inheritdoc
 */
class GetAssignedSalesChannelsForStockCache implements GetAssignedSalesChannelsForStockInterface
{
    /**
     * @var GetAssignedSalesChannelsForStock
     */
    private $getAssignedSalesChannelsForStock;

    /**
     * @var array
     */
    private $salesChannelsAssignedToStocks = [];

    /**
     * @param GetAssignedSalesChannelsForStock $getAssignedSalesChannelsForStock
     */
    public function __construct(
        GetAssignedSalesChannelsForStock $getAssignedSalesChannelsForStock
    ) {
        $this->getAssignedSalesChannelsForStock = $getAssignedSalesChannelsForStock;
    }

    /**
     * @inheritdoc
     */
    public function execute(int $stockId): array
    {
        if (!isset($this->salesChannelsAssignedToStocks[$stockId])) {
            $assignedSalesChannels = $this->getAssignedSalesChannelsForStock->execute($stockId);
            if (empty($assignedSalesChannels)) {
                return $assignedSalesChannels;
            }
            $this->salesChannelsAssignedToStocks[$stockId] = $assignedSalesChannels;
        }

        return $this->salesChannelsAssignedToStocks[$stockId];
    }
}

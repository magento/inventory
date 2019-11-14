<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;

/**
 * @inheritdoc
 */
class GetStockBySalesChannelCache implements GetStockBySalesChannelInterface
{
    /**
     * @var GetStockBySalesChannel
     */
    private $getStockBySalesChannel;

    /**
     * @var array
     */
    private $stocksBySalesChannels = [[]];

    /**
     * @param GetStockBySalesChannel $getStockBySalesChannel
     */
    public function __construct(
        GetStockBySalesChannel $getStockBySalesChannel
    ) {
        $this->getStockBySalesChannel = $getStockBySalesChannel;
    }

    /**
     * @inheritdoc
     */
    public function execute(SalesChannelInterface $salesChannel): StockInterface
    {
        $type = $salesChannel->getType();
        $code = $salesChannel->getCode();
        if (!isset($this->stocksBySalesChannels[$type][$code])) {
            $this->stocksBySalesChannels[$type][$code] = $this->getStockBySalesChannel->execute($salesChannel);
        }

        return $this->stocksBySalesChannels[$type][$code];
    }
}

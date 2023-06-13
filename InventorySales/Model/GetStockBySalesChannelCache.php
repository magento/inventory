<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;

/**
 * @inheritdoc
 */
class GetStockBySalesChannelCache implements GetStockBySalesChannelInterface, ResetAfterRequestInterface
{
    /**
     * @var GetStockBySalesChannel
     */
    private $getStockBySalesChannel;

    /**
     * @var int[]
     */
    private $channelCodes = [];

    /**
     * @param GetStockBySalesChannel $getStockBySalesChannel
     */
    public function __construct(
        GetStockBySalesChannel $getStockBySalesChannel
    ) {
        $this->getStockBySalesChannel = $getStockBySalesChannel;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->channelCodes = [];
    }

    /**
     * @inheritdoc
     */
    public function execute(\Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel)
        : \Magento\InventoryApi\Api\Data\StockInterface
    {
        $code = $salesChannel->getCode();
        $type = $salesChannel->getType();
        $hash = sha1($code . $type);
        if (!isset($this->channelCodes[$hash]) || null === $code) {
            $this->channelCodes[$hash] = $this->getStockBySalesChannel->execute($salesChannel);
        }

        return $this->channelCodes[$hash];
    }
}

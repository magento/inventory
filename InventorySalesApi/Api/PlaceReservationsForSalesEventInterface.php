<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api;

/**
 * This service is responsible for creating reservations upon a sale event.
 *
 * @api
 */
interface PlaceReservationsForSalesEventInterface
{
    /**
     * Creates reservations for items in a sales event, associating them with a sales channel and event details.
     *
     * @param \Magento\InventorySalesApi\Api\Data\ItemToSellInterface[] $items
     * @param \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel
     * @param \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent
     * @return void
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute(
        array $items,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel,
        \Magento\InventorySalesApi\Api\Data\SalesEventInterface $salesEvent
    ): void;
}

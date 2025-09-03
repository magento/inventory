<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api\Data;

/**
 * TODO: temporal fix of extension classes generation during installation
 * ExtensionInterface class for @see \Magento\InventoryApi\Api\Data\StockInterface
 */
interface StockExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * Gets stock extension attributes like sales channels for a stock entity as an array of SalesChannelInterface.
     *
     * @return \Magento\InventorySalesApi\Api\Data\SalesChannelInterface[]|null
     */
    public function getSalesChannels(): ?array;

    /**
     * Sets the sales channels for a stock entity as an array of `SalesChannelInterface` objects or null.
     *
     * @param \Magento\InventorySalesApi\Api\Data\SalesChannelInterface[]|null $salesChannels
     * @return void
     */
    public function setSalesChannels(?array $salesChannels): void;
}

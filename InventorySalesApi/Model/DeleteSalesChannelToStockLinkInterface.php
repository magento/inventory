<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

/**
 * Delete link between Stock and Sales Channel (Service Provider Interface - SPI)
 *
 * @api
 */
interface DeleteSalesChannelToStockLinkInterface
{
    /**
     * Deletes the link between a stock and a sales channel based on the provided type and code parameters.
     *
     * @param string $type
     * @param string $code
     * @return void
     */
    public function execute(string $type, string $code): void;
}

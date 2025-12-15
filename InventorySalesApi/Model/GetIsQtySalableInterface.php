<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

/**
 * Service which determines if the qty in stock is salable
 */
interface GetIsQtySalableInterface
{
    /**
     * Finds whether the qty in stock is salable
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(string $sku, int $stockId): bool;
}

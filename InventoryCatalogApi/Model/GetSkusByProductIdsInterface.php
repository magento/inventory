<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Provides all product SKUs by ProductIds. Key is product id, value is sku
 * @api
 */
interface GetSkusByProductIdsInterface
{
    /**
     * Retrieves SKUs by product IDs, returning an array where keys are product IDs and values are SKUs.
     *
     * @param array $productIds
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(array $productIds): array;
}

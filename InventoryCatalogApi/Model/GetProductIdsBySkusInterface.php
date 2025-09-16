<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Provides all product SKUs by ProductIds. Key is sku, value is product id
 * @api
 */
interface GetProductIdsBySkusInterface
{
    /**
     * Executes the retrieval of product IDs by SKUs, returning an array where keys are SKUs and values are IDs.
     *
     * @param array $skus
     * @return array
     * @throws NoSuchEntityException
     */
    public function execute(array $skus): array;
}

<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

/**
 * Get product types id by product skus.
 *
 * @api
 */
interface GetProductTypesBySkusInterface
{
    /**
     * Retrieves product types by SKUs, returning an array where keys are SKUs and values are product types.
     *
     * @param array $skus
     * @return array (key: 'sku', value: 'product_type')
     */
    public function execute(array $skus);
}

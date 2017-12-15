<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model;

/**
 * Product ID locator provides all product SKUs by ProductIds.
 * @api
 */
interface ProductSkuLocatorInterface
{
    /**
     * Returns associative array of product skus as key and type as value grouped by ProductIds.
     *
     * @param array $productIds
     * @return array
     */
    public function retrieveSkusByProductIds(array $productIds): array;
}

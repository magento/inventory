<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Api;

/**
 * Get product type id by product id.
 *
 * @api
 */
interface GetProductTypeByIdInterface
{
    /**
     * Returns product type id by product id.
     *
     * @param int $productId
     * @return string|null
     */
    public function execute(int $productId);
}

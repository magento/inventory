<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAlert\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Is product salable
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface ProductIsSalableInterface
{
    /**
     * @param ProductInterface $product
     * @param string           $websiteCode
     * @param string           $salesChannel
     *
     * @return bool
     */
    public function isSalable(
        ProductInterface $product,
        string $websiteCode,
        string $salesChannel = SalesChannelInterface::TYPE_WEBSITE
    ): bool;
}

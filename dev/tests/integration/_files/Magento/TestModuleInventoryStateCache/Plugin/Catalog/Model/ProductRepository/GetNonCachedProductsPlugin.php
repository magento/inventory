<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestModuleInventoryStateCache\Plugin\Catalog\Model\ProductRepository;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Get non-cached products for integration tests plugin.
 */
class GetNonCachedProductsPlugin
{
    /**
     * Retrieve non-cached product by sku.
     *
     * @param ProductRepositoryInterface $subject
     * @param \Closure $proceed
     * @param string $sku
     * @param bool $editMode
     * @param int|null $storeId
     * @param bool $forceReload
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGet(
        ProductRepositoryInterface $subject,
        \Closure $proceed,
        $sku,
        $editMode = false,
        $storeId = null,
        $forceReload = false
    ): ProductInterface {
        return $proceed($sku, $editMode, $storeId, true);
    }
}

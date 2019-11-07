<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Plugin;

use Magento\Inventory\Model\ResourceModel\IsProductAssignedToStock;

/**
 * Caching plugin for IsProductAssignedToStock service.
 */
class IsProductAssignedToStockCache
{
    /**
     * @var array
     */
    private $skuToStockIdAssignation = [];

    /**
     * Cache service result to avoid multiple database calls for same item
     *
     * @param IsProductAssignedToStock $subject
     * @param callable $proceed
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function aroundExecute(IsProductAssignedToStock $subject, callable $proceed, string $sku, int $stockId): bool
    {
        if (!isset($this->skuToStockIdAssignation[$sku][$stockId])) {
            $this->skuToStockIdAssignation[$sku][$stockId] = $proceed($sku, $stockId);
        }
        return $this->skuToStockIdAssignation[$sku][$stockId];
    }
}

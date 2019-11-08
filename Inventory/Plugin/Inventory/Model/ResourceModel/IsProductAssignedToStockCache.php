<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Plugin\Inventory\Model\ResourceModel;

use Magento\Inventory\Model\ResourceModel\IsProductAssignedToStock;

/**
 * Caching plugin for IsProductAssignedToStock service.
 */
class IsProductAssignedToStockCache
{
    /**
     * @var array
     */
    private $skuToStockIdAssignment = [];

    /**
     * Cache service result to avoid multiple database calls for same item
     *
     * @param IsProductAssignedToStock $subject
     * @param callable $proceed
     * @param string $sku
     * @param int $stockId
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(IsProductAssignedToStock $subject, callable $proceed, string $sku, int $stockId): bool
    {
        if (!isset($this->skuToStockIdAssignment[$sku][$stockId])) {
            $this->skuToStockIdAssignment[$sku][$stockId] = $proceed($sku, $stockId);
        }
        return $this->skuToStockIdAssignment[$sku][$stockId];
    }
}

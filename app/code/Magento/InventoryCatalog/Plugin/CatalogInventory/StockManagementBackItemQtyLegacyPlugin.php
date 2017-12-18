<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventoryCatalog\Plugin\CatalogInventory;

/**
 * Class provides around Plugin on \Magento\CatalogInventory\Model\StockManagement::backItemQty
 */
class StockManagementBackItemQtyLegacyPlugin
{
    /**
     * @var \Magento\CatalogInventory\Api\StockManagementInterface
     */
    private $returnProduct;

    /**
     * StockManagementBackItemQtyLegacyPlugin constructor.
     *
     * @param \Magento\CatalogInventory\Api\StockManagementInterface $returnProduct
     */
    public function __construct(
        \Magento\CatalogInventory\Api\StockManagementInterface $returnProduct
    ) {
        $this->returnProduct = $returnProduct;
    }

    /**
     * @param \Magento\CatalogInventory\Model\StockManagement $subject
     * @param \Closure $closure
     * @param int $productId
     * @param float $qty
     * @param int $scopeId
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundBackItemQty($subject, $closure, $productId, $qty, $scopeId = null)
    {
        return $this->returnProduct->backItemQty($productId, $qty, $scopeId);
    }
}

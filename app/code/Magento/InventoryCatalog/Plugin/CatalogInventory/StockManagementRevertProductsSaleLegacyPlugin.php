<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventoryCatalog\Plugin\CatalogInventory;

use Magento\InventorySalesApi\Api\RevertProductSaleInterface;

/**
 * Class provides around Plugin on \Magento\CatalogInventory\Model\StockManagement::revertProductsSale
 */
class StockManagementRevertProductsSaleLegacyPlugin
{
    /**
     * @var RevertProductSaleInterface
     */
    private $revertProductSale;

    /**
     * StockManagementRevertProductsSaleLegacyPlugin constructor.
     *
     * @param RevertProductSaleInterface $revertProductSale
     */
    public function __construct(
        RevertProductSaleInterface $revertProductSale
    ) {
        $this->revertProductSale = $revertProductSale;
    }

    /**
     * @param \Magento\CatalogInventory\Model\StockManagement $subject
     * @param \Closure                                        $closure
     * @param string[]                                        $items
     * @param int|null                                        $websiteId
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundRevertProductsSale($subject, $closure, $items, $websiteId = null)
    {
        return $this->revertProductSale->execute($items, $websiteId);
    }
}

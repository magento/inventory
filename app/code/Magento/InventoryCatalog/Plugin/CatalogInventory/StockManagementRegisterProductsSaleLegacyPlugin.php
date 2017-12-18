<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventoryCatalog\Plugin\CatalogInventory;

use Magento\InventorySalesApi\Api\RegisterProductSaleInterface;

/**
 * Class provides around Plugin on \Magento\CatalogInventory\Model\StockManagement::registerProductsSale
 */
class StockManagementRegisterProductsSaleLegacyPlugin
{
    /**
     * @var RegisterProductSaleInterface
     */
    private $registerProductSale;

    /**
     * StockManagementRegisterProductsSaleLegacyPlugin constructor.
     *
     * @param RegisterProductSaleInterface $registerProductSale
     */
    public function __construct(
        RegisterProductSaleInterface $registerProductSale
    ) {
        $this->registerProductSale = $registerProductSale;
    }

    /**
     * @param \Magento\CatalogInventory\Model\StockManagement $subject
     * @param \Closure                                        $closure
     * @param float[]                                         $items
     * @param int|null                                        $websiteId
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundRegisterProductsSale($subject, $closure, $items, $websiteId = null)
    {
        return $this->registerProductSale->execute($items, $websiteId);
    }
}

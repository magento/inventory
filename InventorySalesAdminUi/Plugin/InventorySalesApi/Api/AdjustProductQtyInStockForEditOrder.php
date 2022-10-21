<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Plugin\InventorySalesApi\Api;

use Magento\Backend\Model\Session\Quote;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventorySalesApi\Model\GetSalableQtyInterface;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class AdjustProductQtyInStockForEditOrder
{
    /**
     * @var Quote
     */
    private $adminQuoteSession;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param Quote $adminQuoteSession
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        Quote $adminQuoteSession,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->adminQuoteSession = $adminQuoteSession;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Adjust salable quantity with quantity ordered
     *
     * @param GetSalableQtyInterface $subject
     * @param float $qty
     * @param string $sku
     * @param int $stockId
     * @return float
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        GetSalableQtyInterface $subject,
        float $qty,
        string $sku,
        int $stockId
    ): float {
        $order = $this->adminQuoteSession->getOrder();
        $orderedQty = 0;
        if ($order && $order->getId()) {
            $id = $this->getProductIdsBySkus->execute([$sku])[$sku];
            foreach ($order->getAllItems() as $item) {
                if ((int) $item->getProductId() === $id) {
                    $orderedQty += $item->getQtyOrdered();
                }
            }
        }

        return $qty + $orderedQty;
    }
}

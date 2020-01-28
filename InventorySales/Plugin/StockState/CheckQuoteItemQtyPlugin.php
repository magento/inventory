<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\StockState;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySales\Model\Stock\StockStateProvider;
use Magento\CatalogInventory\Api\StockStateInterface;

/**
 * CheckQuoteItemQtyPlugin Class
 *
 * Replace legacy quote item check. Passes call onto class
 * Magento\InventorySales\Model\Stock\StockStateProvider
 */
class CheckQuoteItemQtyPlugin
{
    /**
     * @var StockStateProvider
     */
    private $stockStateProvider;

    /**
     * @param StockStateProvider $stockStateProvider
     */
    public function __construct(
        StockStateProvider $stockStateProvider
    ) {
        $this->stockStateProvider = $stockStateProvider;
    }

    /**
     * Plugin for \Magento\CatalogInventory\Api\StockStateInterface::checkQuoteItemQty()
     *
     * @param StockStateInterface $subject
     * @param \Closure $proceed
     * @param int $productId
     * @param float $itemQty
     * @param float $qtyToCheck
     * @param float $origQty
     * @param int|null $scopeId
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheckQuoteItemQty(
        StockStateInterface $subject,
        \Closure $proceed,
        $productId,
        $itemQty,
        $qtyToCheck,
        $origQty,
        $scopeId = null
    ) {
        return (
            $this->stockStateProvider->checkQuoteItemQty(
                $productId,
                $itemQty,
                $qtyToCheck,
                $origQty,
                $scopeId
            )
        );
    }
}

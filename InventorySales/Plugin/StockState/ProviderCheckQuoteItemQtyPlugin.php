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
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface as OriginalStockStateProvider;
use Magento\CatalogInventory\Api\Data\StockItemInterface;

/**
 * ProviderCheckQuoteItemQtyPlugin Class
 *
 * Replace legacy quote item check. Passes call onto class
 * Magento\InventorySales\Model\Stock\StockStateProvider
 */
class ProviderCheckQuoteItemQtyPlugin
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
     * Plugin for \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface::checkQuoteItemQty()
     *
     * @param OriginalStockStateProvider $subject
     * @param \Closure $proceed
     * @param StockItemInterface $stockItem
     * @param float $qty
     * @param float $summaryQty
     * @param float $origQty
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheckQuoteItemQty(
        OriginalStockStateProvider $subject,
        \Closure $proceed,
        StockItemInterface $stockItem,
        $qty,
        $summaryQty,
        $origQty = 0.0
    ) {
        return (
            $this->stockStateProvider->checkQuoteItemQty(
                $stockItem->getProductId(),
                $qty,
                $summaryQty,
                $origQty,
                $stockItem->getExtensionAttributes()->getWebsiteId()
            )
        );
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\StockState;

use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\FormatInterface;
use Magento\InventoryCatalog\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\CheckQuoteItemQty;

class CheckQuoteItemQtyPlugin
{
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var FormatInterface
     */
    private $format;

    /**
     * @var CheckQuoteItemQty
     */
    private $checkQuoteItemQty;

    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param FormatInterface $format
     * @param CheckQuoteItemQty $checkQuoteItemQty
     */
    public function __construct(
        GetSkusByProductIdsInterface $getSkusByProductIds,
        FormatInterface $format,
        CheckQuoteItemQty $checkQuoteItemQty
    ) {
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->format = $format;
        $this->checkQuoteItemQty = $checkQuoteItemQty;
    }

    /**
     * @param StockStateInterface $subject
     * @param \Closure $proceed
     * @param int $productId
     * @param float $itemQty
     * @param float $qtyToCheck
     * @param float $origQty
     * @param int|null $scopeId
     *
     * @return DataObject
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheckQuoteItemQty(
        StockStateInterface $subject,
        \Closure $proceed,
        $productId,
        $itemQty,
        $qtyToCheck,
        $origQty,
        $scopeId
    ) {
        $qty = $this->getNumber($itemQty);
        $productSku = $this->getSkusByProductIds->execute([$productId])[$productId];

        $result = $this->checkQuoteItemQty->execute($productSku, $qty);

        return $result;
    }

    /**
     * @param string|float|int|null $qty
     *
     * @return float|null
     */
    private function getNumber($qty)
    {
        if (!is_numeric($qty)) {
            return $this->format->getNumber($qty);
        }

        return $qty;
    }
}

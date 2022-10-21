<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\StockState;

use Magento\Framework\App\ObjectManager;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySales\Model\GetBackorder;

/**
 * Replace legacy quote item check
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckQuoteItemQtyPlugin
{
    /**
     * @var GetBackorder
     */
    private $getBackorder;

    /**
     * @param GetBackorder|null $getBackorder
     */
    public function __construct(
        GetBackorder $getBackorder = null
    ) {
        $this->getBackorder = $getBackorder
            ?? ObjectManager::getInstance()->get(GetBackorder::class);
    }

    /**
     * Replace legacy quote item check
     *
     * @param StockStateInterface $subject
     * @param \Closure $proceed
     * @param int $productId
     * @param float $itemQty
     * @param float $qtyToCheck
     * @param float $origQty
     * @param int|null $scopeId
     *
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
        return $this->getBackorder->execute((int) $productId, $itemQty, $qtyToCheck, $scopeId);
    }
}

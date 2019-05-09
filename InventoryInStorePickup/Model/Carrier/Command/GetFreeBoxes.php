<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Carrier\Command;

/**
 * Calculate and return number of items with free delivery.
 */
class GetFreeBoxes
{
    /**
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     *
     * @return float
     */
    public function execute(\Magento\Quote\Model\Quote\Address\RateRequest $request): float
    {
        $freeBoxes = 0.0;
        if ($request->getAllItems()) {
            /** @var \Magento\Quote\Model\Quote\Item\AbstractItem $item */
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    $freeBoxes += $this->getFreeBoxesCountFromChildren($item);
                } elseif ($item->getFreeShipping()) {
                    $freeBoxes += $item->getQty();
                }
            }
        }

        return $freeBoxes;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return float
     */
    private function getFreeBoxesCountFromChildren(\Magento\Quote\Model\Quote\Item\AbstractItem $item): float
    {
        $freeBoxes = 0.0;
        foreach ($item->getChildren() as $child) {
            if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                $freeBoxes += $item->getQty() * $child->getQty();
            }
        }

        return $freeBoxes;
    }
}

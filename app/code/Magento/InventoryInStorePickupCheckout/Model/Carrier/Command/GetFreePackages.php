<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupCheckout\Model\Carrier\Command;

use Magento\InventoryInStorePickupCheckoutApi\Model\Carrier\Command\GetFreePackagesInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * @inheritdoc
 */
class GetFreePackages implements GetFreePackagesInterface
{
    /**
     * @inheritdoc
     */
    public function execute(RateRequest $request): float
    {
        $freeBoxes = 0.0;
        if ($request->getAllItems()) {
            /** @var AbstractItem $item */
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
     * @param AbstractItem $item
     *
     * @return float
     */
    private function getFreeBoxesCountFromChildren(AbstractItem $item): float
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

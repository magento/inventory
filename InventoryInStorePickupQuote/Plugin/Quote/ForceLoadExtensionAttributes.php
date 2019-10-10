<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Closure;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteRepository\LoadHandler;

/**
 * By default, shipping assignments are not loaded for inactive quote.
 *
 * For admin panel, it is always inactive.
 */
class ForceLoadExtensionAttributes
{
    /**
     * Activate backend cart to add store pickup information.
     *
     * @param LoadHandler $loadHandler
     * @param Closure $proceed
     * @param CartInterface $cart
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundLoad(
        LoadHandler $loadHandler,
        Closure $proceed,
        CartInterface $cart
    ) {
        $isActive = $cart->getIsActive();
        $proceed($cart->setIsActive(true));
        $cart->setIsActive($isActive);
    }
}

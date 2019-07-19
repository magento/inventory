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
 * For admin panel, it is always inactive.
 */
class ForceLoadExtensionAttributes
{
    /**
     * @param LoadHandler $loadHandler
     * @param Closure $method
     * @param CartInterface $cart
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundLoad(
        LoadHandler $loadHandler,
        Closure $method,
        CartInterface $cart
    ) {
        $isActive = $cart->getIsActive();

        $method($cart->setIsActive(true));

        $cart->setIsActive($isActive);
    }
}

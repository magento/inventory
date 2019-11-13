<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupMultishipping\Plugin\Checkout\Controller\Index\Index;

use Magento\Checkout\Model\Cart;

/**
 * Turns Off multiple address checkout for Quote.
 *
 * @TODO remove when fix from core will be delivered. @see https://github.com/magento/magento2/pull/24072
 */
class DisableMultishippingPlugin
{
    /**
     * @var Cart
     */
    private $cart;

    /**
     * @param Cart $cart
     */
    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * Disable multiple address checkout.
     *
     * @param \Magento\Framework\App\Action\Action $subject
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(\Magento\Framework\App\Action\Action $subject): void
    {
        $quote = $this->cart->getQuote();
        if ($quote->getIsMultiShipping()) {
            $quote->setIsMultiShipping(0);
            $this->cart->saveQuote();
        }
    }
}

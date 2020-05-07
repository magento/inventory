<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupMultishipping\Test\Unit\Controller\Checkout;

use Magento\Checkout\Controller\Index\Index;
use Magento\Checkout\Model\Cart;
use Magento\InventoryInStorePickupMultishipping\Plugin\Checkout\Controller\Index\Index\DisableMultishippingPlugin;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for plugin which turns Off Multishipping mode for Quote.
 */
class PluginTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $cartMock;

    /**
     * @var MockObject
     */
    private $quoteMock;

    /**
     * @var DisableMultishippingPlugin
     */
    private $object;

    protected function setUp(): void
    {
        $this->cartMock = $this->createMock(Cart::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->addMethods(['setIsMultiShipping', 'getIsMultiShipping'])
            ->onlyMethods(['__wakeUp'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->cartMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);
        $this->object = new DisableMultishippingPlugin(
            $this->cartMock
        );
    }

    public function testExecuteTurnsOffMultishippingModeOnMultishippingQuote(): void
    {
        $subject = $this->createMock(Index::class);
        $this->quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(1);
        $this->quoteMock->expects($this->once())->method('setIsMultiShipping')->with(0);
        $this->cartMock->expects($this->once())->method('saveQuote');
        $this->object->beforeExecute($subject);
    }

    public function testExecuteTurnsOffMultishippingModeOnNotMultishippingQuote(): void
    {
        $subject = $this->createMock(Index::class);
        $this->quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(0);
        $this->quoteMock->expects($this->never())->method('setIsMultiShipping');
        $this->cartMock->expects($this->never())->method('saveQuote');
        $this->object->beforeExecute($subject);
    }
}

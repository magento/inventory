<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Test\Unit\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\TestFramework\Unit\Helper\MockCreationTrait;
use Magento\InventoryInStorePickupQuote\Plugin\Checkout\ShippingInformationManagementPlugin;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressExtensionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ShippingInformationManagementPlugin.
 */
class ShippingInformationManagementPluginTest extends TestCase
{
    use MockCreationTrait;

    /**
     * Test subject.
     *
     * @var ShippingInformationManagementPlugin
     */
    private $plugin;

    /**
     * @var ShippingInformationManagement|MockObject
     */
    private $subject;

    /**
     * @var ShippingInformationInterface|MockObject
     */
    private $addressInformation;

    /**
     * @var AddressInterface|MockObject
     */
    private $shippingAddress;

    /**
     * @var AddressInterface|MockObject
     */
    private $billingAddress;

    /**
     * @var AddressInterface|MockObject
     */
    private $extensionAttributes;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->subject = $this->createMock(ShippingInformationManagement::class);
        $this->addressInformation = $this->createMock(ShippingInformationInterface::class);
        $this->shippingAddress = $this->createMock(AddressInterface::class);
        $this->billingAddress = $this->createMock(AddressInterface::class);
        $this->extensionAttributes = $this->createPartialMockWithReflection(
            AddressExtensionInterface::class,
            ['getPickupLocationCode']
        );
        $this->plugin = new ShippingInformationManagementPlugin();
    }

    /**
     * Test beforeSaveAddressInformation when it's pickup store with incomplete billing
     *
     * @return void
     */
    public function testBeforeSaveAddressInformationPickupStoreIncompleteBilling(): void
    {
        $cartId = 123;
        $pickupLocationCode = 'store_001';
        $this->shippingAddress->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributes);
        $this->extensionAttributes->expects($this->once())
            ->method('getPickupLocationCode')
            ->willReturn($pickupLocationCode);
        $this->addressInformation->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddress);
        $this->addressInformation->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($this->billingAddress);
        $this->addressInformation->expects($this->never())->method('setBillingAddress');
        $result = $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
        $this->assertEquals([$cartId, $this->addressInformation], $result);
    }

    /**
     * Test beforeSaveAddressInformation when it's pickup store with complete billing
     *
     * @return void
     */
    public function testBeforeSaveAddressInformationPickupStoreCompleteBilling(): void
    {
        $cartId = 123;
        $pickupLocationCode = 'store_001';
        $this->shippingAddress->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributes);
        $this->extensionAttributes->expects($this->once())
            ->method('getPickupLocationCode')
            ->willReturn($pickupLocationCode);
        $this->addressInformation->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddress);
        $this->addressInformation->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($this->billingAddress);
        $this->addressInformation->expects($this->never())->method('setBillingAddress');
        $result = $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
        $this->assertEquals([$cartId, $this->addressInformation], $result);
    }

    /**
     * Test beforeSaveAddressInformation when it's not pickup store and billing is null
     *
     * @return void
     */
    public function testBeforeSaveAddressInformationNotPickupStore(): void
    {
        $cartId = 123;
        $this->shippingAddress->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributes);
        $this->extensionAttributes->expects($this->once())
            ->method('getPickupLocationCode')
            ->willReturn(null);
        $this->shippingAddress->expects($this->once())->method('getStreet')->willReturn(['123 Main St']);
        $this->shippingAddress->expects($this->once())->method('getCity')->willReturn('Austin');
        $this->addressInformation->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddress);
        $this->addressInformation->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn(null);
        $this->addressInformation->expects($this->once())
            ->method('setBillingAddress')
            ->with($this->shippingAddress);
        $result = $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
        $this->assertEquals([$cartId, $this->addressInformation], $result);
    }

    /**
     * Test billing is not copied when shipping address was cleared after pickup reset
     *
     * @return void
     */
    public function testBeforeSaveAddressInformationDoesNotCopyIncompleteShippingAddressToBilling(): void
    {
        $cartId = 123;
        $this->shippingAddress->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributes);
        $this->extensionAttributes->expects($this->once())
            ->method('getPickupLocationCode')
            ->willReturn(null);
        $this->shippingAddress->expects($this->once())->method('getStreet')->willReturn([]);
        $this->shippingAddress->expects($this->never())->method('getCity');
        $this->addressInformation->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($this->shippingAddress);
        $this->addressInformation->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn(null);
        $this->addressInformation->expects($this->never())->method('setBillingAddress');
        $result = $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
        $this->assertEquals([$cartId, $this->addressInformation], $result);
    }
}

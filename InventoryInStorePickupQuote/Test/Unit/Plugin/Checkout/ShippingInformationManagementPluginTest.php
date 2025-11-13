<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Test\Unit\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
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
        $this->subject = $this->getMockBuilder(ShippingInformationManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressInformation = $this->getMockBuilder(ShippingInformationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->shippingAddress = $this->getMockBuilder(AddressInterface::class)
            ->onlyMethods(['getExtensionAttributes'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->billingAddress = $this->getMockBuilder(AddressInterface::class)
            ->onlyMethods(
                [
                    'getFirstname',
                    'getLastname',
                    'getStreet',
                    'getCity',
                    'getPostcode',
                    'getTelephone',
                    'getRegionId',
                    'getCountryId'
                ]
            )
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->extensionAttributes = $this->getMockBuilder(AddressExtensionInterface::class)
            ->addMethods(['getPickupLocationCode'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
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
}

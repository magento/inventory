<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuoteGraphQl\Test\Unit\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\TestFramework\Unit\Helper\MockCreationTrait;
use Magento\InventoryInStorePickupQuoteGraphQl\Plugin\Checkout\ClearPickupLocationOnNonPickupShippingMethodPlugin;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressExtensionFactory;
use Magento\Quote\Api\Data\AddressExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for ClearPickupLocationOnNonPickupShippingMethodPlugin.
 */
class ClearPickupLocationOnNonPickupShippingMethodPluginTest extends TestCase
{
    use MockCreationTrait;

    /**
     * Test subject
     *
     * @var ClearPickupLocationOnNonPickupShippingMethodPlugin
     */
    private ClearPickupLocationOnNonPickupShippingMethodPlugin $plugin;

    /** @var ShippingInformationManagement|MockObject */
    private $subject;

    /** @var ShippingInformationInterface|MockObject */
    private $addressInformation;

    /** @var CartRepositoryInterface|MockObject */
    private $cartRepository;

    /** @var AddressExtensionFactory|MockObject */
    private $addressExtensionFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->subject = $this->createMock(ShippingInformationManagement::class);
        $this->addressInformation = $this->createMock(ShippingInformationInterface::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->addressExtensionFactory = $this->createMock(AddressExtensionFactory::class);
        $this->plugin = new ClearPickupLocationOnNonPickupShippingMethodPlugin(
            $this->addressExtensionFactory,
            $this->cartRepository
        );
    }

    /**
     * @return void
     */
    public function testDoesNothingWhenMethodIsInStorePickup(): void
    {
        $cartId = 42;
        $this->addressInformation->method('getShippingCarrierCode')->willReturn('instore');
        $this->addressInformation->method('getShippingMethodCode')->willReturn('pickup');
        $this->cartRepository->expects($this->never())->method('getActive');

        $result = $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );

        $this->assertSame([$cartId, $this->addressInformation], $result);
    }

    /**
     * @return void
     */
    public function testDoesNothingWhenCarrierCodeIsNull(): void
    {
        $cartId = 42;
        $this->addressInformation->method('getShippingCarrierCode')->willReturn(null);
        $this->addressInformation->method('getShippingMethodCode')->willReturn('flatrate');
        $this->cartRepository->expects($this->never())->method('getActive');

        $result = $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );

        $this->assertSame([$cartId, $this->addressInformation], $result);
    }

    /**
     * The request is establishing in-store pickup (it carries a pickup location) on a cart that is
     * not yet a pickup order, so the plugin must keep the address even with a placeholder carrier.
     *
     * @return void
     */
    public function testKeepsPickupStateWhenRequestEstablishesPickupOnNonPickupQuote(): void
    {
        $cartId = 42;
        $incomingExtension = $this->createExtensionMock('apple-store');
        $incomingAddress = $this->createAddressMock($incomingExtension);
        $quoteExtension = $this->createExtensionMock(null);
        $quoteShippingAddress = $this->createAddressMock($quoteExtension);
        $quoteShippingAddress->method('getShippingMethod')->willReturn(null);

        $incomingExtension->expects($this->never())->method('setPickupLocationCode');
        $incomingAddress->expects($this->never())->method('setFirstname');
        $incomingAddress->expects($this->never())->method('setStreet');
        $incomingAddress->expects($this->never())->method('setCity');

        $this->configureFlatrateMethod();
        $quote = $this->createMock(Quote::class);
        $quote->method('getShippingAddress')->willReturn($quoteShippingAddress);
        $this->cartRepository->method('getActive')->with($cartId)->willReturn($quote);
        $this->addressInformation->method('getShippingAddress')->willReturn($incomingAddress);

        $result = $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );

        $this->assertSame([$cartId, $this->addressInformation], $result);
    }

    /**
     * Switching an existing in-store pickup order to home delivery must clear the pickup location and
     * reset the store address even though the reused quote address still carries the pickup code.
     *
     * @return void
     */
    public function testClearsPickupWhenSwitchingExistingPickupOrderToDelivery(): void
    {
        $cartId = 42;
        $extension = $this->createExtensionMock('apple-store');
        $shippingAddress = $this->createAddressMock($extension);

        $extension->expects($this->once())->method('setPickupLocationCode')->with(null);
        $this->expectAddressReset($shippingAddress);

        $this->configureFlatrateMethod();
        $this->configureQuoteWithShippingAddress($cartId, $shippingAddress);

        $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
    }

    /**
     * @return void
     */
    public function testDoesNotResetAddressForNormalDeliveryCheckout(): void
    {
        $cartId = 42;
        $shippingExtension = $this->createExtensionMock(null);
        $quoteExtension = $this->createExtensionMock(null);
        $shippingAddress = $this->createAddressMock($shippingExtension);
        $quoteShippingAddress = $this->createAddressMock($quoteExtension);
        $quoteShippingAddress->method('getShippingMethod')->willReturn('flatrate_flatrate');

        $shippingExtension->expects($this->once())->method('setPickupLocationCode')->with(null);
        $quoteExtension->expects($this->once())->method('setPickupLocationCode')->with(null);
        $shippingAddress->expects($this->never())->method('setFirstname');
        $shippingAddress->expects($this->never())->method('setStreet');
        $shippingAddress->expects($this->never())->method('setCity');

        $this->configureFlatrateMethod();
        $quote = $this->createMock(Quote::class);
        $quote->method('getShippingAddress')->willReturn($quoteShippingAddress);
        $this->cartRepository->method('getActive')->with($cartId)->willReturn($quote);
        $this->addressInformation->method('getShippingAddress')->willReturn($shippingAddress);

        $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
    }

    /**
     * @return void
     */
    public function testClearsPickupCodeOnBothAddressesWhenDifferentFromIncoming(): void
    {
        $cartId = 42;
        $incomingExtension = $this->createExtensionMock(null);
        $quoteExtension = $this->createExtensionMock('apple-store');
        $incomingAddress = $this->createAddressMock($incomingExtension);
        $quoteShippingAddress = $this->createAddressMock($quoteExtension);

        $incomingExtension->expects($this->once())->method('setPickupLocationCode')->with(null);
        $quoteExtension->expects($this->once())->method('setPickupLocationCode')->with(null);
        $this->expectAddressReset($incomingAddress);
        $this->expectAddressReset($quoteShippingAddress);

        $this->configureFlatrateMethod();
        $quote = $this->createMock(Quote::class);
        $quote->method('getShippingAddress')->willReturn($quoteShippingAddress);
        $this->cartRepository->method('getActive')->with($cartId)->willReturn($quote);
        $this->addressInformation->method('getShippingAddress')->willReturn($incomingAddress);

        $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
    }

    /**
     * @return void
     */
    public function testInitializesExtensionAttributesWhenMissing(): void
    {
        $cartId = 42;
        $extension = $this->createExtensionMock(null);
        $shippingAddress = $this->createAddressMock(null);

        $this->addressExtensionFactory->expects($this->once())->method('create')->willReturn($extension);
        $extension->expects($this->once())->method('setPickupLocationCode')->with(null);
        $shippingAddress->expects($this->once())->method('setExtensionAttributes')->with($extension);
        $shippingAddress->expects($this->never())->method('setStreet');

        $this->configureFlatrateMethod();
        $this->configureQuoteWithShippingAddress($cartId, $shippingAddress);

        $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
    }

    /**
     * @return void
     */
    public function testResetsAddressWhenQuoteShippingMethodIsStillInStorePickup(): void
    {
        $cartId = 42;
        $extension = $this->createExtensionMock(null);
        $shippingAddress = $this->createAddressMock($extension);
        $quoteShippingAddress = $this->createAddressMock($extension);
        $quoteShippingAddress->method('getShippingMethod')->willReturn(InStorePickup::DELIVERY_METHOD);

        $extension->expects($this->exactly(2))->method('setPickupLocationCode')->with(null);
        $this->expectAddressReset($shippingAddress);
        $this->expectAddressReset($quoteShippingAddress);

        $this->configureFlatrateMethod();
        $quote = $this->createMock(Quote::class);
        $quote->method('getShippingAddress')->willReturn($quoteShippingAddress);
        $this->cartRepository->method('getActive')->with($cartId)->willReturn($quote);
        $this->addressInformation->method('getShippingAddress')->willReturn($shippingAddress);

        $this->plugin->beforeSaveAddressInformation(
            $this->subject,
            $cartId,
            $this->addressInformation
        );
    }

    /**
     * @param Address|MockObject $address
     * @return void
     */
    private function expectAddressReset(MockObject $address): void
    {
        $address->expects($this->once())->method('setFirstname')->with(null);
        $address->expects($this->once())->method('setLastname')->with(null);
        $address->expects($this->once())->method('setCompany')->with(null);
        $address->expects($this->once())->method('setStreet')->with([]);
        $address->expects($this->once())->method('setCity')->with(null);
        $address->expects($this->once())->method('setPostcode')->with(null);
    }

    /**
     * @param int $cartId
     * @param Address|MockObject $shippingAddress
     * @return void
     */
    private function configureQuoteWithShippingAddress(int $cartId, MockObject $shippingAddress): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getShippingAddress')->willReturn($shippingAddress);
        $this->cartRepository->method('getActive')->with($cartId)->willReturn($quote);
        $this->addressInformation->method('getShippingAddress')->willReturn($shippingAddress);
    }

    /**
     * @return void
     */
    private function configureFlatrateMethod(): void
    {
        $this->addressInformation->method('getShippingCarrierCode')->willReturn('flatrate');
        $this->addressInformation->method('getShippingMethodCode')->willReturn('flatrate');
    }

    /**
     * @param AddressExtensionInterface|MockObject|null $extension
     * @return Address|MockObject
     */
    private function createAddressMock(?MockObject $extension): MockObject
    {
        $address = $this->getMockBuilder(Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $address->method('getExtensionAttributes')->willReturn($extension);

        return $address;
    }

    /**
     * @param string|null $pickupLocationCode
     * @return AddressExtensionInterface|MockObject
     */
    private function createExtensionMock(?string $pickupLocationCode): MockObject
    {
        $extension = $this->createPartialMockWithReflection(
            AddressExtensionInterface::class,
            ['getPickupLocationCode', 'setPickupLocationCode']
        );
        $extension->method('getPickupLocationCode')->willReturn($pickupLocationCode);

        return $extension;
    }
}

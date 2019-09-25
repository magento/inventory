<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickup\Model\ExtractSourceAddressData;
use Magento\InventoryInStorePickupAdminUi\Model\SourceToQuoteAddress as ToQuoteAddress;
use Magento\InventoryInStorePickupQuote\Model\GetShippingAddressData;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Get Shipping Address from Source by its sourceCode and original Shipping Address
 */
class GetShippingAddressBySourceCodeAndOriginalAddress
{
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var ExtractSourceAddressData
     */
    private $dataExtractor;

    /**
     * @var ToQuoteAddress
     */
    private $sourceToQuoteAddress;

    /**
     * @var GetShippingAddressData
     */
    private $getShippingAddressData;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param ExtractSourceAddressData $dataExtractor
     * @param ToQuoteAddress $sourceToQuoteAddress
     * @param GetShippingAddressData $getShippingAddressData
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository,
        ExtractSourceAddressData $dataExtractor,
        ToQuoteAddress $sourceToQuoteAddress,
        GetShippingAddressData $getShippingAddressData
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->dataExtractor = $dataExtractor;
        $this->sourceToQuoteAddress = $sourceToQuoteAddress;
        $this->getShippingAddressData = $getShippingAddressData;
    }

    /**
     * Get Shipping Address from Source by its source code and original Shipping Address
     *
     * @param string $sourceCode
     * @param AddressInterface $originalAddress
     *
     * @return AddressInterface|null
     * @throws NoSuchEntityException
     */
    public function execute(string $sourceCode, AddressInterface $originalAddress): ?AddressInterface
    {
        $source = $this->sourceRepository->get($sourceCode);
        if (!$source->getExtensionAttributes() || !$source->getExtensionAttributes()->getIsPickupLocationActive()) {
            return null;
        }

        return $this->sourceToQuoteAddress->convert($source, $originalAddress);
    }
}

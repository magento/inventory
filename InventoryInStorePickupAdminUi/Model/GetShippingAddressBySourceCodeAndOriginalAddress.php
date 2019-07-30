<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress;
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
     * @var ExtractSourceShippingAddressData
     */
    private $dataExtractor;

    /**
     * @var ToQuoteAddress
     */
    private $sourceToQuoteAddress;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param ExtractSourceShippingAddressData $dataExtractor
     * @param ToQuoteAddress $sourceToQuoteAddress
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository,
        ExtractSourceShippingAddressData $dataExtractor,
        ToQuoteAddress $sourceToQuoteAddress
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->dataExtractor = $dataExtractor;
        $this->sourceToQuoteAddress = $sourceToQuoteAddress;
    }

    /**
     * Get Shipping Address from Source by its sourceCode and original Shipping Address
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

        $addressData = $this->dataExtractor->execute($source);

        /** @noinspection PhpParamsInspection */
        return $this->sourceToQuoteAddress->convert($addressData, $originalAddress);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

class LoadInStorePickupOnGetListPlugin
{
    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * LoadInStorePickupOnGetListPlugin constructor.
     *
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionAttributesFactory
     */
    public function __construct(ExtensionAttributesFactory $extensionAttributesFactory)
    {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * Enrich the given Source Objects with the In-Store pickup attribute
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceSearchResultsInterface $sourceSearchResults
     *
     * @return SourceSearchResultsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        SourceRepositoryInterface $subject,
        SourceSearchResultsInterface $sourceSearchResults
    ):SourceSearchResultsInterface {
        foreach ($sourceSearchResults->getItems() as $source) {
            $extensionAttributes = $source->getExtensionAttributes();

            if ($extensionAttributes === null) {
                $extensionAttributes = $this->extensionAttributesFactory->create(SourceInterface::class);
                $source->setExtensionAttributes($extensionAttributes);
            }

            $pickupAvailable = $source->getData(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE);
            $extensionAttributes->setIsPickupLocationActive((bool)$pickupAvailable);
        }

        return $sourceSearchResults;
    }
}

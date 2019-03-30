<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

class LoadInStorePickupOnGetListPlugin
{
    /**
     * Enrich the given Source Objects with the In-Store pickup attribute
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceSearchResultsInterface $sourceSearchResults
     *
     * @return SourceSearchResultsInterface
     */
    public function afterGetList(
        SourceRepositoryInterface $subject,
        SourceSearchResultsInterface $sourceSearchResults
    ):SourceSearchResultsInterface {
        foreach ($sourceSearchResults->getItems() as $source) {
            $extensionAttributes = $source->getExtensionAttributes();

            $pickupAvailable = $source->getData(PickupLocationInterface::IN_STORE_PICKUP_CODE);
            $extensionAttributes->setIsPickupLocationActive((bool)$pickupAvailable);
        }

        return $sourceSearchResults;
    }
}

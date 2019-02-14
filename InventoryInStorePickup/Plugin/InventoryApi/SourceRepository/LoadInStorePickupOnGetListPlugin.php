<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryInStorePickupApi\Api\Data\InStorePickupInterface;

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
        $sources = [];

        foreach ($sourceSearchResults->getItems() as $source) {
            $pickupAvailable = $source->getData(InStorePickupInterface::IN_STORE_PICKUP_CODE);

            $extensionAttributes = $source->getExtensionAttributes();
            $extensionAttributes->setInStorePickup($pickupAvailable);

            $source->setExtensionAttributes($extensionAttributes);

            $sources[] = $source;
        }
        $sourceSearchResults->setItems($sources);

        return $sourceSearchResults;
    }
}

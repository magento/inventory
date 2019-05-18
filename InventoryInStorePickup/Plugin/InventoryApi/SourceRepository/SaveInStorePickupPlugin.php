<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface as Location;

class SaveInStorePickupPlugin
{
    /**
     * Persist the In-Store pickup attribute on Source save
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface|DataObject $source
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ): array {
        $extensionAttributes = $source->getExtensionAttributes();

        if ($extensionAttributes !== null) {
            $source->setData(Location::IS_PICKUP_LOCATION_ACTIVE, $extensionAttributes->getIsPickupLocationActive());
            $source->setData(Location::FRONTEND_NAME, $extensionAttributes->getFrontendName());
            $source->setData(Location::FRONTEND_DESCRIPTION, $extensionAttributes->getFrontendDescription());
        }

        return [$source];
    }
}

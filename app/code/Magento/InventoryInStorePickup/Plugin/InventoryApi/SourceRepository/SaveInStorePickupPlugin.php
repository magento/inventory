<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceExtensionInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface as Location;

/**
 * Set data to Source itself from its extension attributes to save these values to `inventory_source` DB table.
 */
class SaveInStorePickupPlugin
{
    /**
     * Persist the In-Store pickup attribute on Source save
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface $source
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ): array {
        if (!$source instanceof DataObject) {
            return [$source];
        }

        $extensionAttributes = $source->getExtensionAttributes();
        $this->setFrontendName($source, $extensionAttributes);

        if ($extensionAttributes !== null) {
            $source->setData(Location::IS_PICKUP_LOCATION_ACTIVE, $extensionAttributes->getIsPickupLocationActive());
            $source->setData(Location::FRONTEND_DESCRIPTION, $extensionAttributes->getFrontendDescription());
        }

        return [$source];
    }

    /**
     * Set Frontend Name to Source.
     * Extension Attributes are not set and Source Frontend Name is missed -> use Source Name
     * Extension Attributes are not set and Source Frontend Name is set -> do nothing
     * Extension Attributes are set and Frontend Name attribute is missed -> use Source Name
     * Extension Attributes are set and Frontend Name attribute is set -> use Frontend Name attribute
     *
     * @param SourceInterface|DataObject $source
     * @param SourceExtensionInterface|null $extensionAttributes
     */
    private function setFrontendName(SourceInterface $source, ?SourceExtensionInterface $extensionAttributes): void
    {
        if ($extensionAttributes === null && $source->getData(Location::FRONTEND_NAME) === null ||
            $extensionAttributes && !$extensionAttributes->getFrontendName()
        ) {
            $source->setData(Location::FRONTEND_NAME, $source->getName());
            return;
        }

        if ($extensionAttributes) {
            $source->setData(Location::FRONTEND_NAME, $extensionAttributes->getFrontendName());
        }
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface as Location;

/**
 * Set data to Source itself from its extension attributes to save these values to `inventory_source` DB table.
 */
class SaveInStorePickupPlugin
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     */
    public function __construct(ExtensionAttributesFactory $extensionAttributesFactory)
    {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

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
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceInterface::class);
            /** @noinspection PhpParamsInspection */
            $source->setExtensionAttributes($extensionAttributes);
        }

        if (empty($extensionAttributes->getFrontendName())) {
            $extensionAttributes->setFrontendName($source->getName());
        }

        $source->setData(Location::IS_PICKUP_LOCATION_ACTIVE, $extensionAttributes->getIsPickupLocationActive());
        $source->setData(Location::FRONTEND_DESCRIPTION, $extensionAttributes->getFrontendDescription());
        $source->setData(Location::FRONTEND_NAME, $extensionAttributes->getFrontendName());

        return [$source];
    }
}

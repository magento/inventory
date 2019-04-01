<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

class LoadInStorePickupOnGetPlugin
{
    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * LoadInStorePickupOnGetPlugin constructor.
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
     * @param SourceInterface $source
     *
     * @return SourceInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ):SourceInterface {
        $pickupAvailable = $source->getData(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE);

        $extensionAttributes = $source->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceInterface::class);
            $source->setExtensionAttributes($extensionAttributes);
        }

        $extensionAttributes->setIsPickupLocationActive((bool)$pickupAvailable);

        return $source;
    }
}

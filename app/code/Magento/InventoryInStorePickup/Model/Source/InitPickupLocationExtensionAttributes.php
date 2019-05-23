<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Source;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Set store-pickup related source extension attributes
 */
class InitPickupLocationExtensionAttributes
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
     * @param SourceInterface $source
     */
    public function execute(SourceInterface $source): void
    {
        if (!$source instanceof DataObject) {
            return;
        }
        $pickupAvailable = $source->getData(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE);
        $frontendName = $source->getData(PickupLocationInterface::FRONTEND_NAME);
        $frontendDescription = $source->getData(PickupLocationInterface::FRONTEND_DESCRIPTION);

        $extensionAttributes = $source->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceInterface::class);
            /** @noinspection PhpParamsInspection */
            $source->setExtensionAttributes($extensionAttributes);
        }

        $extensionAttributes
            ->setIsPickupLocationActive((bool)$pickupAvailable)
            ->setFrontendName($frontendName)
            ->setFrontendDescription($frontendDescription);
    }
}

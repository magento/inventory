<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Observer\Source;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryInStorePickupApi\Api\Data\InStorePickupInterface;

/**
 * Populate source with In-Store pickup status during saving via controller
 *
 * This needs to be handled in dedicated observer, because there is no pre-defined way of making several API calls for
 * Form submission handling
 */
class PopulateWithInStorePickupObserver implements ObserverInterface
{
    /**
     * Populate source with In-Store pickup status during saving via controller
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $source = $observer->getEvent()->getSource();
        $request = $observer->getEvent()->getRequest();
        $requestData = $request->getParams();

        $extensionAttributes = $source->getExtensionAttributes();

        if (isset($requestData['general'][InStorePickupInterface::EXTENSION_ATTRIBUTES_KEY][InStorePickupInterface::IN_STORE_PICKUP_CODE])) {
            $extensionAttributes->setInStorePickup($requestData['general'][InStorePickupInterface::EXTENSION_ATTRIBUTES_KEY][InStorePickupInterface::IN_STORE_PICKUP_CODE]);
            $source->setExtensionAttributes($extensionAttributes);
        }

    }
}

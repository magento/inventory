<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;

class SaveInStorePickupPlugin
{
    /**
     * Persist the In-Store pickup attribute on Source save
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface $source
     *
     * @return array
     */
    public function beforeSave(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ):array {
        $extensionAttributes = $source->getExtensionAttributes();

        if ($extensionAttributes->getInStorePickup() !== null) {
            $source->setInStorePickup($extensionAttributes->getInStorePickup());
        }
        
        return [$source];
    }
}

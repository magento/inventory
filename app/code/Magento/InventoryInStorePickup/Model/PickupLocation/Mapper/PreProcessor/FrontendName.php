<?php
/**
 *  Copyright © Magento, Inc. All rights reserved.
 *  See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\PickupLocation\Mapper\PreProcessor;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper\PreProcessorInterface;

class FrontendName implements PreProcessorInterface
{

    /**
     * Process Source Field before pass it to Pickup Location
     *
     * @param SourceInterface $source
     * @param string $value Frontend Name Extension Attribute value
     *
     * @return string
     */
    public function process(SourceInterface $source, $value): string
    {
        if (empty($value)) {
            $value = $source->getName();
        }

        return $value;
    }
}

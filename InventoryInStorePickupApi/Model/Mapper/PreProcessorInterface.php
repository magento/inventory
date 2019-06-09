<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\Mapper;

use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Source Field pre-processing before field value will be passed to Pickup Location.
 *
 * @api
 */
interface PreProcessorInterface
{
    /**
     * Process Source Field before pass it to Pickup Location
     *
     * @param SourceInterface $source
     * @param mixed $value Source Field Value
     *
     * @return mixed
     */
    public function process(SourceInterface $source, $value);
}

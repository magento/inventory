<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Inventory\Model;

interface SourceSelectionInterface
{
    /**
     * @param $store
     * @param $items
     * @param $destinationAddress
     * @return Package[]
     */
    public function getPackages($store, $items, $destinationAddress);
}

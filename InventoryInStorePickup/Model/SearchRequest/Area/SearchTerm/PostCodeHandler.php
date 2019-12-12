<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area\SearchTerm;

class PostCodeHandler
{
    /**
     * @param string $searchTerm
     * @return string
     */
    public function execute(string $searchTerm) : string
    {
        return $searchTerm;
    }
}

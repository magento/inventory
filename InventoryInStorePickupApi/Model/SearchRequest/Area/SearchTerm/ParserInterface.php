<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm;

use Magento\Framework\DataObject;

/**
 * Search Term parser interface.
 * @api
 */
interface ParserInterface
{
    /**
     * Handle search term parsing.
     *
     * @param string $searchTerm
     * @param DataObject $result
     *
     * @return void
     */
    public function execute(string $searchTerm, DataObject $result): void;
}

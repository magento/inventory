<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\Indexer;

use Magento\Indexer\Model\Config;

/**
 * Remove old catalogInventory index
 */
class RemoveOldCatalogInventoryIndexPlugin
{
    private const CATALOGINVENTORY_INDEX = 'cataloginventory_stock';

    /**
     * Remove old cataloginventory_stock index
     *
     * @param Config $subject
     * @param $result
     * @return array
     */
    public function afterGetIndexers(
        Config $subject,
        $result
    ) {
        unset($result[self::CATALOGINVENTORY_INDEX]);
        return $result;
    }
}

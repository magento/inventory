<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\OptionStockHandler;

use Magento\InventoryBundleProductIndexer\Indexer\OptionStockHandlerInterface;
use Magento\Bundle\Api\Data\OptionInterface;

/**
 * Class SelectOptionStockHandler
 */
class SelectOptionStockHandler implements OptionStockHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function isOptionInStock(OptionInterface $option, array $stock): bool
    {
        return true;
    }
}

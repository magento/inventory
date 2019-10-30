<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\OptionStockHandler;

use Magento\InventoryBundleProductIndexer\Indexer\OptionStockHandlerInterface;

/**
 * Class CheckboxOptionStockHandler
 */
class CheckboxOptionStockHandler implements OptionStockHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function isOptionInStock(array $option, array $stockId): bool
    {
        return true;
    }
}

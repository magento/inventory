<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\OptionStockHandler;

use Magento\Bundle\Api\Data\OptionInterface;
use Magento\InventoryBundleProductIndexer\Indexer\OptionStockHandlerInterface;

/**
 * Class CheckboxOptionStockHandler
 */
class CheckboxOptionStockHandler implements OptionStockHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function isOptionInStock(OptionInterface $option, array $stockId): bool
    {
        foreach ($option->getProductLinks() as $productLink) {

        }
        return false;
    }
}

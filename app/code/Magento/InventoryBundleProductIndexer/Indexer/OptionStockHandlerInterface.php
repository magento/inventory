<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Bundle\Api\Data\OptionInterface;

/**
 * Interface OptionStockResolverInterface
 */
interface OptionStockHandlerInterface
{
    /**
     * Provides stock status of all simple products which take part in bundle option
     *
     * @param OptionInterface $option
     * @param array $stockId
     *
     * @return bool
     */
    public function isOptionInStock(OptionInterface $option, array $stockId): bool;
}

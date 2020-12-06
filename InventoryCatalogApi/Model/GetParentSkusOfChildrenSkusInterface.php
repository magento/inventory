<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

/**
 * Provides relational parent product SKUs by given children SKUs
 */
interface GetParentSkusOfChildrenSkusInterface
{
    /**
     * Returns parent SKUs of children SKUs.
     * Resulting array is like:
     * ```php
     * [
     *     'simple1' => [],
     *     'configurable1-red' => ['configurable1', 'configurable2'],
     *     'configurable1-blue' => ['configurable1'],
     * ]
     * ```
     *
     * @param string[] $skus Children SKUs
     * @return array[] Array of parents SKUs arrays that belong to Children SKUs
     */
    public function execute(array $skus): array;
}

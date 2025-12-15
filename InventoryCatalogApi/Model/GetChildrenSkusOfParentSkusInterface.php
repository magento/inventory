<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

/**
 * Provides relational children product SKUs by given parent SKUs
 */
interface GetChildrenSkusOfParentSkusInterface
{
    /**
     * Returns children SKUs of parent SKUs.
     *
     * Resulting array is like:
     * ```php
     * [
     *     'bundle1' => [],
     *     'configurable1' => ['configurable1-red', 'configurable1-green'],
     *     'grouped1' => ['simple1'],
     * ]
     * ```
     *
     * @param string[] $skus Parents SKUs
     * @return array<string, string[]> Array of children SKUs arrays that belong to parents SKUs
     */
    public function execute(array $skus): array;
}

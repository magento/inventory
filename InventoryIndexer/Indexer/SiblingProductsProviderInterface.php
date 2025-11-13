<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

interface SiblingProductsProviderInterface
{
    /**
     * Retrieve sibling product SKUs for a given SKUs.
     *
     * @param string[] $skus
     * @return string[]
     */
    public function getSkus(array $skus): array;
}

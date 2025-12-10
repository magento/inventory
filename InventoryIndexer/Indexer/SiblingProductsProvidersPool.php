<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

class SiblingProductsProvidersPool
{
    /**
     * @param SiblingProductsProviderInterface[] $siblingProductsProviders
     */
    public function __construct(
        private readonly array $siblingProductsProviders = [],
    ) {
        (fn (SiblingProductsProviderInterface ...$siblingProductsProviders) => $siblingProductsProviders)(
            ...$this->siblingProductsProviders
        );
    }

    /**
     * Get sibling SKUs grouped by product type.
     *
     * @param string[] $skus
     * @return array<string, string[]>
     */
    public function getSiblingsGroupedByType(array $skus): array
    {
        if (!$skus) {
            return [];
        }

        $result = [];
        foreach ($this->siblingProductsProviders as $productType => $siblingProductsProvider) {
            $siblingSkus = $siblingProductsProvider->getSkus($skus);
            if (!$siblingSkus) {
                continue;
            }
            $result[$productType] = $siblingSkus;
        }

        return $result;
    }
}

<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

interface CompositeProductTypesProviderInterface
{
    /**
     * Returns composite product types.
     *
     * @return string[]
     */
    public function execute(): array;
}

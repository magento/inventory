<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

interface ProductSalabilityChangeProcessorInterface
{
    /**
     * Process salability update
     *
     * @param string[] $skus
     * @return void
     */
    public function execute(array $skus): void;
}

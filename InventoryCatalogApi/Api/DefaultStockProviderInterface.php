<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Api;

/**
 * Service returns Default Stock Id
 *
 * @api
 */
interface DefaultStockProviderInterface
{
    /**
     * Get Default Stock Id
     *
     * @return int
     */
    public function getId(): int;
}

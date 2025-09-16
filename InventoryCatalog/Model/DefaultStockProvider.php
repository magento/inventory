<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

/**
 * Service returns Default Stock Id
 */
class DefaultStockProvider implements DefaultStockProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getId(): int
    {
        return 1;
    }
}

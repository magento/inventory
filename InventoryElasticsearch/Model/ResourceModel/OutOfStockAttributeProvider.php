<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Model\ResourceModel;

use Magento\InventoryCatalog\Model\ResourceModel\OutOfStockAttributeProviderInterface;

class OutOfStockAttributeProvider implements OutOfStockAttributeProviderInterface
{
    /**
     * @inheritDoc
     */
    public function isOutOfStockAttributeExists(): bool
    {
        return true;
    }
}

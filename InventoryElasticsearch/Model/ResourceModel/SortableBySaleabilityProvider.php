<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Model\ResourceModel;

use Magento\InventoryCatalogApi\Model\SortableBySaleabilityInterface;

class SortableBySaleabilityProvider implements SortableBySaleabilityInterface
{
    /**
     * @inheritDoc
     */
    public function isSortableBySaleability(): bool
    {
        return true;
    }
}

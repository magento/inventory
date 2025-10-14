<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\InventoryCatalogApi\Model\SortableBySaleabilityInterface;

class SortableBySaleabilityProvider implements SortableBySaleabilityInterface
{
    /**
     * @inheritDoc
     */
    public function isSortableBySaleability(): bool
    {
        return false;
    }
}

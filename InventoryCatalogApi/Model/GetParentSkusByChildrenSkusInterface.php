<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

/**
 * Provides relational parent product SKUs by given children SKUs
 */
interface GetParentSkusByChildrenSkusInterface
{
    /**
     * Returns parent SKUs by children SKUs
     *
     * @param string[] $skus Children SKUs
     * @return string[] Parent SKUs
     */
    public function execute(array $skus): array;
}

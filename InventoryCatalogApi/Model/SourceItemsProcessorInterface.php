<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

/**
 * Process(save|delete|replace) source items for given product sku.
 *
 * @api
 */
interface SourceItemsProcessorInterface
{
    /**
     * Save, delete or replace source items for given product sku.
     *
     * @param string $sku
     * @param array $sourceItemsData
     * @throws \Magento\Framework\Exception\InputException in case source items data is not valid.
     */
    public function execute(string $sku, array $sourceItemsData): void;
}

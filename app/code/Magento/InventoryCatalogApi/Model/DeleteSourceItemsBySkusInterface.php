<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

/**
 * Delete source items by given product skus service.
 *
 * @api
 */
interface DeleteSourceItemsBySkusInterface
{
    /**
     * Delete source items by product skus.
     *
     * @param array $skus
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function execute(array $skus): void;
}

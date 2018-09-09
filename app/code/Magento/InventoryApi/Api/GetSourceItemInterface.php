<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Sugar service for find SourceItems by SKU and source code
 *
 * @api
 */
interface GetSourceItemInterface
{
    /**
     * @param string $sku
     * @param string $sourceCode
     * @return SourceItemInterface
     * @throws NoSuchEntityException
     */
    public function execute(string $sku, string $sourceCode): SourceItemInterface;
}

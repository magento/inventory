<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

/**
 * Sugar service to retrieve source type by source code
 *
 */
interface GetSourceTypeBySourceCodeInterface
{
    /**
     * @param string $sourceCode
     * @return string
     */
    public function execute(string $sourceCode): string;
}

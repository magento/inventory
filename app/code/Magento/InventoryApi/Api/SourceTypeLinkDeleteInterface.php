<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api;

/**
 * Service method for source type link delete
 * Performance efficient API
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface SourceTypeLinkDeleteInterface
{
    /**
     * @param string $sourceCode
     */
    public function execute(string $sourceCode): void;
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Service method for source type link save
 * Performance efficient API
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 */
interface SourceTypeLinkSaveInterface
{
    /**
     * Save SourceTypeLink data
     *
     * @param SourceInterface $source
     * @return void
     * @throws InputException
     * @throws CouldNotSaveException
     */
    public function execute(SourceInterface $source): void;
}

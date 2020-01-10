<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api;

use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\CouldNotSaveException;
use Dotenv\Exception\ValidationException;

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
     * @param SourceTypeLinkInterface $link
     * @return void
     * @throws InputException
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function execute(SourceTypeLinkInterface $link): void;
}

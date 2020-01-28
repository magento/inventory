<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * It is extension point for source type link (Service Provider Interface - SPI)
 * Provide own implementation of this interface if you would like to replace source type
 *
 */
interface SourceTypeLinkManagementInterface
{
    const SOURCE_CODE = 'source_code';

    /**
     * Save Type link by source
     *
     * Get type source link from source object and save its.
     *
     * @param SourceInterface $source
     * @return void
     */
    public function saveTypeLinksBySource(SourceInterface $source): void;

    /**
     * Load type source link by source and set its to source object
     *
     * @param SourceInterface $source
     * @return SourceInterface
     */
    public function loadTypeLinksBySource(SourceInterface $source): SourceInterface;
}

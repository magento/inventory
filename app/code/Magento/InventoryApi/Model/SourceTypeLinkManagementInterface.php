<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * It is extension point for carrier links storage replacing (Service Provider Interface - SPI)
 * Provide own implementation of this interface if you would like to replace storage
 *
 * @api
 */
interface SourceTypeLinkManagementInterface
{
    const SOURCE_CODE = 'source_code';

    /**
     * Save Type link by source
     *
     * Get carrier links from source object and save its. If carrier links are equal to null do nothing
     *
     * @param SourceInterface $source
     * @return void
     */
    public function saveTypeLinksBySource(SourceInterface $source): void;

    /**
     * Load carrier links by source and set its to source object
     *
     * @param SourceInterface $source
     * @return SourceInterface
     */
    public function loadTypeLinksBySource(SourceInterface $source): SourceInterface;
}

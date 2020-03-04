<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\UpdateInventory;

/**
 * Inventory data model for legacy stock items mass update.
 */
class InventoryData
{
    /**
     * @var string[]
     */
    private $skus;

    /**
     * @var string
     */
    private $data;

    /**
     * @param string[] $skus
     * @param string $data
     */
    public function __construct(array $skus, string $data)
    {
        $this->skus = $skus;
        $this->data = $data;
    }

    /**
     * Retrieve products skus for update.
     *
     * @return string[]
     */
    public function getSkus(): array
    {
        return $this->skus;
    }

    /**
     * Retrieve inventory data for update.
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }
}

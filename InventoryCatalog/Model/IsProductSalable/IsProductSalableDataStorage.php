<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\IsProductSalable;

/**
 * Is product salable service cache.
 */
class IsProductSalableDataStorage
{
    /**
     * @var array
     */
    private $statuses = [];

    /**
     * Save product is salable status to cache for given stock.
     *
     * @param string $sku
     * @param int $stockId
     * @param bool $status
     * @return void
     */
    public function setIsSalable(string $sku, int $stockId, bool $status): void
    {
        $this->statuses[$sku][$stockId] = $status;
    }

    /**
     * Get is salable status for given product and stock.
     *
     * @param string $sku
     * @param int $stockId
     * @return bool|null
     */
    public function getIsSalable(string $sku, int $stockId): ?bool
    {
        return $this->statuses[$sku][$stockId] ?? null;
    }

    /**
     * Clean is salable status for given product and stock.
     *
     * @param string $sku
     * @param int $stockId
     * @return void
     */
    public function removeIsSalable(string $sku, int $stockId): void
    {
        unset($this->statuses[$sku][$stockId]);
    }

    /**
     * Clean is salable statuses for all saved products and stocks.
     *
     * @return void
     */
    public function cleanIsSalable(): void
    {
        $this->statuses = [];
    }
}

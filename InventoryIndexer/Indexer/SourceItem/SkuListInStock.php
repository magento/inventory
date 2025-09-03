<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

/**
 * Represents relation between stock and sku list
 */
class SkuListInStock
{
    /**
     * @var int
     */
    private $stockId;

    /**
     * @var array
     */
    private $skuList;

    /**
     * Returns the stock ID associated with the current instance.
     *
     * @return int
     */
    public function getStockId(): int
    {
        return $this->stockId;
    }

    /**
     * Sets the stock ID for the current instance.
     *
     * @param int $stockId
     * @return void
     */
    public function setStockId(int $stockId)
    {
        $this->stockId = $stockId;
    }

    /**
     * Retrieves the list of SKUs associated with the current instance.
     *
     * @return array
     */
    public function getSkuList(): array
    {
        return $this->skuList;
    }

    /**
     * Assigns a list of SKUs to the current instance.
     *
     * @param array $skuList
     * @return void
     */
    public function setSkuList(array $skuList)
    {
        $this->skuList = $skuList;
    }
}

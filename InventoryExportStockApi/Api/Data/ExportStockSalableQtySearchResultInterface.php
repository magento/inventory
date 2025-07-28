<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStockApi\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for ExportStockSalableQtySearchResult
 * @api
 */
interface ExportStockSalableQtySearchResultInterface extends SearchResultsInterface
{
    /**
     * @inheritdoc
     */
    public function getItems();

    /**
     * Set stock data array
     *
     * @param array $items
     * @return $this
     */
    public function setItems(array $items);
}

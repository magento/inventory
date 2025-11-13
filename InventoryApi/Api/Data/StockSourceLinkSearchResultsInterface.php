<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api\Data;

/**
 * Search results of Repository::getList method
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface StockSourceLinkSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get StockSourceLink list
     *
     * @return \Magento\InventoryApi\Api\Data\StockSourceLinkInterface[]
     */
    public function getItems();

    /**
     * Set StockSourceLink list
     *
     * @param \Magento\InventoryApi\Api\Data\StockSourceLinkInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}

<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Indexer\Config\Converter\SortingAdjustmentInterface;
use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexer;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockIndexer;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;

class SortingAdjustment implements SortingAdjustmentInterface
{
    /**
     * @inheritDoc
     */
    public function adjust(array $indexersList) : array
    {
        $indexersListAdjusted = $indexersList;

        $order = array_keys($indexersListAdjusted);
        $inventoryPos = array_search(InventoryIndexer::INDEXER_ID, $order);
        $stockPos = array_search(StockIndexer::INDEXER_ID, $order);
        if ($stockPos !== false && $inventoryPos !== false) {
            foreach ($indexersListAdjusted as $id => $data) {
                if ($id === StockIndexer::INDEXER_ID) {
                    $indexersListAdjusted = [$id => $data] + $indexersListAdjusted;
                    break;
                }
            }
        }

        $order = array_keys($indexersListAdjusted);
        $pricePos = array_search(PriceIndexer::INDEXER_ID, $order);
        $inventoryPos = array_search(InventoryIndexer::INDEXER_ID, $order);
        if ($pricePos !== false && $inventoryPos !== false) {
            $indexersListAdjusted = $this->switchPositions($indexersListAdjusted, $inventoryPos, $pricePos);
        }

        return $indexersListAdjusted;
    }

    /**
     * Switch position for two indexers if necessary
     *
     * @param array $list
     * @param int $posShouldBeUpper
     * @param int $posShouldBeLower
     * @return array
     */
    private function switchPositions(array $list, int $posShouldBeUpper, int $posShouldBeLower) : array
    {
        if ($posShouldBeUpper > $posShouldBeLower) {
            $newOrder = $this->reArrange($list, $posShouldBeUpper, $posShouldBeLower);
            $tmpList = [];
            $c = count($newOrder);
            for ($i = 0; $i < $c; $i++) {
                $tmpList[$newOrder[$i]] = $list[$newOrder[$i]];
            }
            $list = $tmpList;
        }
        return $list;
    }

    /**
     * Perform adjustments in the sorting order
     *
     * @param array $list
     * @param int $posShouldBeUpper
     * @param int $posShouldBeLower
     * @return array
     */
    private function reArrange(array $list, int $posShouldBeUpper, int $posShouldBeLower) : array
    {
        $newOrder = [];
        $order = array_keys($list);
        foreach ($order as $pos => $indexerId) {
            if ($pos < $posShouldBeLower || $pos > $posShouldBeUpper) {
                $newOrder[$pos] = $indexerId;
            } elseif ($pos === $posShouldBeLower) {
                $newOrder[$pos] = $order[$posShouldBeUpper];
                $newOrder[$pos+1] = $indexerId;
            } elseif ($pos > $posShouldBeLower && $pos < $posShouldBeUpper) {
                $newOrder[$pos+1] = $indexerId;
            }
        }
        return $newOrder;
    }
}

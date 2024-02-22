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
use Magento\InventoryIndexer\Indexer\InventoryIndexer;

class SortingAdjustment implements SortingAdjustmentInterface
{
    /**
     * @inheridoc
     */
    public function adjust(array $indexersList): array
    {
        $indexersListAdjusted = [];
        $order = array_keys($indexersList);

        $pricePos = array_search(PriceIndexer::INDEXER_ID, $order);
        $inventoryPos = array_search(InventoryIndexer::INDEXER_ID, $order);
        if ($pricePos === false || $inventoryPos === false || $inventoryPos < $pricePos) {
            return $indexersList;
        }

        $newOrder = [];
        foreach ($order as $pos => $indexerId) {
            if ($pos < $pricePos || $pos > $inventoryPos) {
                $newOrder[$pos] = $indexerId;
            } elseif ($pos === $pricePos) {
                $newOrder[$pos] = $order[$inventoryPos];
                $newOrder[$pos+1] = $indexerId;
            } elseif ($pos > $pricePos && $pos < $inventoryPos) {
                $newOrder[$pos+1] = $indexerId;
            }
        }
        for ($i = 0; $i < count($newOrder); $i++) {
            $indexersListAdjusted[$newOrder[$i]] = $indexersList[$newOrder[$i]];
        }

        return $indexersListAdjusted;
    }
}

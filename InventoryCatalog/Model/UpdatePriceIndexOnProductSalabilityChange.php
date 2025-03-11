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

use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexProcessor;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Model\ProductSalabilityChangeProcessorInterface;

class UpdatePriceIndexOnProductSalabilityChange implements ProductSalabilityChangeProcessorInterface
{
    /**
     * @param PriceIndexProcessor $priceIndexProcessor
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        private readonly PriceIndexProcessor $priceIndexProcessor,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skus): void
    {
        $ids = array_values($this->getProductIdsBySkus->execute($skus));
        if (empty($ids)) {
            return;
        }
        if ($this->priceIndexProcessor->isIndexerScheduled()) {
            // this dependency is not configured via mview.xml because we only need to update price index
            // if the stock status changes
            $this->priceIndexProcessor->getIndexer()->getView()->getChangelog()->addList($ids);
        } else {
            $this->priceIndexProcessor->reindexList($ids);
        }
    }
}

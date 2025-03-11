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

namespace Magento\InventoryCatalogSearch\Model;

use Magento\CatalogSearch\Model\Indexer\Fulltext\Processor as FulltextIndexProcessor;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Model\ProductSalabilityChangeProcessorInterface;

class UpdateFulltextIndexOnProductSalabilityChange implements ProductSalabilityChangeProcessorInterface
{
    /**
     * @param FulltextIndexProcessor $fulltextIndexProcessor
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        private readonly FulltextIndexProcessor $fulltextIndexProcessor,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skus): void
    {
        if (!$this->fulltextIndexProcessor->isIndexerScheduled()) {
            // handled via index propagation based on indexers dependencies
            return;
        }
        $ids = array_values($this->getProductIdsBySkus->execute($skus));
        if (empty($ids)) {
            return;
        }
        $this->fulltextIndexProcessor->getIndexer()->getView()->getChangelog()->addList($ids);
    }
}

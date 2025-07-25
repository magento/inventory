<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
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

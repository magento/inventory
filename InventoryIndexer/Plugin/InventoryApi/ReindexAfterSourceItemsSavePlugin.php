<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;

class ReindexAfterSourceItemsSavePlugin
{
    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        GetSourceItemIds $getSourceItemIds,
        SourceItemIndexer $sourceItemIndexer,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Run reindex process for saved source items
     *
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        SourceItemsSaveInterface $subject,
        $result,
        array $sourceItems
    ) {
        $sourceItems = $this->sanitizeSources($sourceItems);
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        if (count($sourceItemIds)) {
            $this->sourceItemIndexer->executeList($sourceItemIds);
        }
    }

    /**
     * Remove items with default source
     *
     * @param array $sourceItems
     * @return array
     */
    private function sanitizeSources(array $sourceItems) : array
    {
        $result = [];
        $defaultCode = $this->defaultSourceProvider->getCode();
        foreach ($sourceItems as $item) {
            if ($item->getSourceCode() !== $defaultCode) {
                $result[] = $item;
            }
        }
        return $result;
    }
}

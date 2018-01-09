<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryApi;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\Inventory\Indexer\SourceItem\SourceItemIndexer;
use Magento\Inventory\Test\Integration\Indexer\GetSourceItemId;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Prototype code
 */
class ReindexSourceItemsAfterSourceItemsSavePlugin
{
    /**
     * @var IndexerInterfaceFactory
     */
    private $indexerFactory;

    /**
     * @var GetSourceItemId
     */
    private $getSourceItemId;

    /**
     * @param IndexerInterfaceFactory $indexerFactory
     * @param GetSourceItemId $getSourceItemId
     */
    public function __construct(IndexerInterfaceFactory $indexerFactory, GetSourceItemId $getSourceItemId)
    {
        $this->indexerFactory = $indexerFactory;
        $this->getSourceItemId = $getSourceItemId;
    }

    /**
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     *
     * @return void
     * @see SourceItemsSaveInterface::execute
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(SourceItemsSaveInterface $subject, $result, array $sourceItems)
    {
        $sourceItemIds = array_map(
            function (SourceItemInterface $sourceItem) {
                return $this->getSourceItemId->execute($sourceItem->getSku(), $sourceItem->getSourceCode());
            },
            $sourceItems
        );

        /** @var IndexerInterface $indexer */
        $indexer = $this->indexerFactory->create();
        $indexer->load(SourceItemIndexer::INDEXER_ID);

        $indexer->reindexList($sourceItemIds);
    }
}

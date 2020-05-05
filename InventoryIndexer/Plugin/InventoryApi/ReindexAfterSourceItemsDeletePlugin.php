<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryIndexer\Indexer\Source\SourceReindexStrategyInterface;

/**
 * Plugin for reindex after source items delete
 */
class ReindexAfterSourceItemsDeletePlugin
{
    /**
     * @var SourceReindexStrategyInterface
     */
    private $sourceReindexStrategyInterface;

    /**
     * @param SourceReindexStrategyInterface $sourceReindexStrategyInterface
     */
    public function __construct(
        SourceReindexStrategyInterface $sourceReindexStrategyInterface
    ) {
        $this->ssourceReindexStrategyInterface = $sourceReindexStrategyInterface;
    }

    /**
     * Method that calls around execution of delete source items
     *
     * @param SourceItemsDeleteInterface $subject
     * @param callable $proceed
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        SourceItemsDeleteInterface $subject,
        callable $proceed,
        array $sourceItems
    ) {
        $sourceCodes = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCodes[] = $sourceItem->getSourceCode();
        }
        $proceed($sourceItems);

        if (count($sourceCodes)) {
            $this->ssourceReindexStrategyInterface->getStrategy()->executeList($sourceCodes);
        }
    }
}

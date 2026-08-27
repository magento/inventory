<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Command;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\InputException;
use Magento\Inventory\Model\IsProductAssignedToStock\CacheStorage;
use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SourceItemsDelete implements SourceItemsDeleteInterface
{
    /**
     * @param DeleteMultiple $deleteMultiple
     * @param LoggerInterface $logger
     * @param CacheStorage $isProductAssignedToStockCacheStorage
     */
    public function __construct(
        private readonly DeleteMultiple $deleteMultiple,
        private readonly LoggerInterface $logger,
        private readonly CacheStorage $isProductAssignedToStockCacheStorage
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $sourceItems): void
    {
        if (empty($sourceItems)) {
            throw new InputException(__('Input data is empty'));
        }
        try {
            $this->deleteMultiple->execute($sourceItems);
            foreach ($sourceItems as $sourceItem) {
                $this->isProductAssignedToStockCacheStorage->delete((string) $sourceItem->getSku());
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete Source Items'), $e);
        }
    }
}

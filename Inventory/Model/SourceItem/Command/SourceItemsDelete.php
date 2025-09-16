<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Command;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\InputException;
use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SourceItemsDelete implements SourceItemsDeleteInterface
{
    /**
     * @var DeleteMultiple
     */
    private $deleteMultiple;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DeleteMultiple $deleteMultiple
     * @param LoggerInterface $logger
     */
    public function __construct(
        DeleteMultiple $deleteMultiple,
        LoggerInterface $logger
    ) {
        $this->deleteMultiple = $deleteMultiple;
        $this->logger = $logger;
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
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete Source Items'), $e);
        }
    }
}

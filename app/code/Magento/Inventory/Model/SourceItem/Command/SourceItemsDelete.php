<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Command;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\InputException;
use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
use Magento\Inventory\Model\ResourceModel\SourceItem\GetSourceItemId;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
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
     * @var SourceItemsReindex
     */
    private $sourceItemsReindex;

    /**
     * @var GetSourceItemId
     */
    private $getSourceItemId;

    /**
     * @param DeleteMultiple $deleteMultiple
     * @param SourceItemsReindex $sourceItemsReindex
     * @param GetSourceItemId $getSourceItemId
     * @param LoggerInterface $logger
     */
    public function __construct(
        DeleteMultiple $deleteMultiple,
        SourceItemsReindex $sourceItemsReindex,
        GetSourceItemId $getSourceItemId,
        LoggerInterface $logger
    ) {
        $this->deleteMultiple = $deleteMultiple;
        $this->logger = $logger;
        $this->sourceItemsReindex = $sourceItemsReindex;
        $this->getSourceItemId = $getSourceItemId;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $sourceItems)
    {
        if (empty($sourceItems)) {
            throw new InputException(__('Input data is empty'));
        }
        try {
            $sourceItemIds = array_map(
                function (SourceItemInterface $sourceItem) {
                    return $this->getSourceItemId->execute($sourceItem->getSku(), $sourceItem->getSourceCode());
                },
                $sourceItems
            );

            $this->deleteMultiple->execute($sourceItems);
            $this->sourceItemsReindex->execute($sourceItemIds);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete Source Items'), $e);
        }
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceTypeLink\Command;

use Exception;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\InputException;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\DeleteMultiple;
use Magento\InventoryApi\Api\SourceTypeLinksDeleteInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SourceTypeLinksDelete implements SourceTypeLinksDeleteInterface
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
    public function execute(array $links): void
    {
        if (empty($links)) {
            throw new InputException(__('Input data is empty'));
        }

        try {
            $this->deleteMultiple->execute($links);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete StockSourceLinks'), $e);
        }
    }
}

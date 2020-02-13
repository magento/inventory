<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceTypeLink\Command;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\InputException;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\Delete;
use Magento\InventoryApi\Api\SourceTypeLinkDeleteInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SourceTypeLinkDelete implements SourceTypeLinkDeleteInterface
{
    /**
     * @var Delete
     */
    private $delete;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Delete $delete
     * @param LoggerInterface $logger
     */
    public function __construct(
        Delete $delete,
        LoggerInterface $logger
    ) {
        $this->delete = $delete;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sourceCode): void
    {
        if (empty($sourceCode)) {
            throw new InputException(__('Input data is empty'));
        }

        try {
            $this->delete->execute($sourceCode);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete SourceTypeLink'), $e);
        }
    }
}

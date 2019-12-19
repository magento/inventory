<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceTypeLink;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink as SourceTypeLinkResourceModel;

class Delete
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
      ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(string $sourceCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->delete(
            $this->resourceConnection->getTableName(SourceTypeLinkResourceModel::TABLE_NAME_SOURCE_TYPE_LINK),
            $connection->quoteInto('source_code = ?', $sourceCode)
        );
    }
}
<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceTypeLink;

use Magento\InventoryApi\Api\Data\SourceExtensionInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink as SourceTypeLinkResourceModel;
use Magento\InventoryApi\Api\Data\SourceTypeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;

/**
 * Implementation of SourceTypeLink save operation for specific db layer
 *
 * Save used here for performance efficient purposes over single save operation
 */
class Save
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

    /**
     * Save source type link
     *
     * @param SourceInterface $source
     */
    public function execute(SourceInterface $source): void
    {
        /** @var SourceExtensionInterface $extensionAttributes */
        $extensionAttributes = $source->getExtensionAttributes();

        $TypeLinkData = [
            'source_code' => $source->getSourceCode(),
            'type_code' => $extensionAttributes->getTypeCode() ?? SourceTypeLinkInterface::DEFAULT_SOURCE_TYPE
        ];

        $this->resourceConnection->getConnection()->insert(
            $this->resourceConnection->getTableName(SourceTypeLinkResourceModel::TABLE_NAME_SOURCE_TYPE_LINK),
            $TypeLinkData
        );
    }
}

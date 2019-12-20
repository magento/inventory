<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceTypeLink;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink as SourceTypeLinkResourceModel;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Save
 *
 */
class Save
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Save constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param SourceInterface $source
     */
    public function execute(SourceInterface $source): void
    {
        $TypeLinkData = [
            'source_code' => $source->getSourceCode(),
            'type_code' => $source->getExtensionAttributes()->getTypeCode()
        ];

        $this->resourceConnection->getConnection()->insert(
            $this->resourceConnection->getTableName(SourceTypeLinkResourceModel::TABLE_NAME_SOURCE_TYPE_LINK),
            $TypeLinkData
        );
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Operation;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceTypeInterface;

/**
 * Create default source types during installation
 */
class CreateDefaultSourceTypes
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * Create default source
     *
     * @return void
     */
    public function execute()
    {
        $connection = $this->resource->getConnection();

        $sourceTypesData[] = [
            SourceTypeInterface::TYPE_CODE => "regular",
            SourceTypeInterface::NAME => "Regular Source"
        ];

        $sourceTypesData[] = [
            SourceTypeInterface::TYPE_CODE => "drop-shipper",
            SourceTypeInterface::NAME => "drop-shipper"
        ];

        $sourceTypesData[] = [
            SourceTypeInterface::TYPE_CODE => "virtual",
            SourceTypeInterface::NAME => "Virtual"
        ];

        foreach ($sourceTypesData as $sourceTypeData) {
            $connection->insert($this->resource->getTableName('inventory_source_type'), $sourceTypeData);
        }
    }
}

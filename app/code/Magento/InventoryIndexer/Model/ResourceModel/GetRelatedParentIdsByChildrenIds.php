<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Provides parent Product ids by related child Product ids.
 */
class GetRelatedParentIdsByChildrenIds
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var string
     */
    private $productTableName;

    /**
     * @var string
     */
    private $relationTableName;

    /**
     * @param ResourceConnection $resource
     * @param string $productTableName
     * @param string $relationTableName
     */
    public function __construct(
        ResourceConnection $resource,
        string $productTableName,
        string $relationTableName
    ) {
        $this->resource = $resource;
        $this->productTableName = $productTableName;
        $this->relationTableName = $relationTableName;
    }

    /**
     * Get parent Product ids by related child Product ids.
     *
     * @param array $childIds
     * @return array
     */
    public function execute(array $childIds): array
    {
        $result = [];
        if (!empty($childIds)) {
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from(
                    ['rt' => $this->resource->getTableName($this->relationTableName)],
                    'parent_id'
                )->join(
                    ['e' => $this->resource->getTableName($this->productTableName)],
                    'e.entity_id = rt.child_id'
                )->where(
                    'e.entity_id IN(?)',
                    $childIds
                )->distinct();

            $result = $connection->fetchCol($select);
        }

        return $result;
    }
}

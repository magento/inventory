<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Get all source codes assigned to given stock and product.
 */
class GetSourceCodesBySkuAndStockId
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param string $sku
     * @param int $stockId
     * @return array
     */
    public function execute(string $sku, int $stockId): array
    {
        $connection = $this->resource->getConnection();
        $sourceLinkTable = $this->resource->getTableName('inventory_source_stock_link');
        $select = $connection->select()
            ->from(
                $sourceLinkTable,
                'source_code'
            )
            ->where('stock_id =?', $stockId)
            ->join(
                ['source' => $this->resource->getTableName('inventory_source_item')],
                'source.source_code = ' . $sourceLinkTable . '.source_code',
                []
            )->where('source.sku = ?', $sku);

        return $connection->fetchCol($select);
    }
}

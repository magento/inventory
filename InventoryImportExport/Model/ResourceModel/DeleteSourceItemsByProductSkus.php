<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Delete source items by given product skus resource.
 */
class DeleteSourceItemsByProductSkus
{
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param ResourceConnection $connection
     */
    public function __construct(ResourceConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Delete source items by skus.
     *
     * @param array $skus
     * @return void
     */
    public function execute(array $skus): void
    {
        $this->connection->getConnection()->delete(
            $this->connection->getTableName('inventory_source_item'),
            ['sku in (?)' => $skus]
        );
    }
}

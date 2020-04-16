<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Model\ProductStatusSelectProcessorInterface;

/**
 * Retrieve product status resource.
 */
class GetProductStatusByProductIdAndStockId
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductStatusSelectProcessorInterface
     */
    private $productStatusSelectProcessor;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductStatusSelectProcessorInterface $productStatusSelectProcessor
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductStatusSelectProcessorInterface $productStatusSelectProcessor
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productStatusSelectProcessor = $productStatusSelectProcessor;
    }

    /**
     * Retrieve product status by product id and stock id.
     *
     * @param int $productId
     * @param int $stockId
     * @return int
     * @throws \Exception
     */
    public function execute(int $productId, int $stockId): int
    {
        $connection = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $sql = $connection->select()
            ->from(['product' => $productTable])
            ->where(
                'product.entity_id = ?',
                $productId
            );
        $sql = $this->productStatusSelectProcessor->execute($sql, $stockId);
        $result = $connection->fetchOne($sql);

        return $result ? Status::STATUS_ENABLED : Status::STATUS_DISABLED;
    }
}

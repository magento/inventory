<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\GetProductTypeByIdInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * @inheritdoc
 */
class GetProductTypeById implements GetProductTypeByIdInterface
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
     * @inheritdoc
     */
    public function execute(int $productId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->getTable('catalog_product_entity'),
                ['type_id']
            )->where(
                'entity_id = ?',
                $productId
            );

        return $connection->fetchOne($select);
    }
}

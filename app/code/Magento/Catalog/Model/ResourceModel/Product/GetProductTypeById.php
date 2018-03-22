<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\GetProductTypeByIdInterface;
use Magento\Catalog\Model\ResourceModel\Product;

/**
 * @inheritdoc
 */
class GetProductTypeById implements GetProductTypeByIdInterface
{
    /**
     * @var Product
     */
    private $productResource;

    /**
     * @param Product $productResource
     */
    public function __construct(
        Product $productResource
    ) {
        $this->productResource = $productResource;
    }

    /**
     * @inheritdoc
     */
    public function execute(int $productId)
    {
        $connection = $this->productResource->getConnection();
        $select = $connection->select()
            ->from(
                $this->productResource->getTable('catalog_product_entity'),
                ['type_id']
            )->where(
                'entity_id = ?',
                $productId
            );

        return $connection->fetchOne($select);
    }
}

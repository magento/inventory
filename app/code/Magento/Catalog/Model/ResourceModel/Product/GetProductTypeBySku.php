<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\GetProductTypeBySkuInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Exception\InputException;

/**
 * @inheritdoc
 */
class GetProductTypeBySku implements GetProductTypeBySkuInterface
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
    public function execute(string $sku)
    {
        if (empty($sku)) {
            throw new InputException(__('Input data is empty'));
        }

        $connection = $this->productResource->getConnection();
        $select = $connection->select()
            ->from(
                $this->productResource->getTable('catalog_product_entity'),
                ['type_id']
            )->where(
                'sku = ?',
                $sku
            );

        return $connection->fetchOne($select);
    }
}

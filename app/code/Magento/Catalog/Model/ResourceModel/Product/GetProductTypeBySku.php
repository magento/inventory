<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\GetProductTypeBySkuInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\InputException;

/**
 * @inheritdoc
 */
class GetProductTypeBySku implements GetProductTypeBySkuInterface
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
    public function execute(string $sku)
    {
        if (empty($sku)) {
            throw new InputException(__('Input data is empty'));
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                $this->getTable('catalog_product_entity'),
                ['type_id']
            )->where(
                'sku = ?',
                $sku
            );

        return $connection->fetchOne($select);
    }
}

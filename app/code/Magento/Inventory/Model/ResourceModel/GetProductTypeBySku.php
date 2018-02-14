<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Get product Type by SKU.
 */
class GetProductTypeBySku
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
     * @param string $sku
     *
     * @return array
     */
    public function execute(string $sku): array
    {
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from(
                ['product' => $this->resource->getTableName('catalog_product_entity')],
                [ProductInterface::TYPE_ID]
            )->where(
                'product.' . ProductInterface::SKU . ' = ?',
                $sku
            );

        return $connection->fetchCol($select);
    }
}

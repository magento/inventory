<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;

class IsProductDisabled
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
     * @inheritdoc
     */
    public function execute(string $sku): bool
    {
        $connection = $this->resource->getConnection();
        $attributeIdSelect = $connection->select()
            ->from(['ea' => $this->resource->getTableName('eav_attribute')], 'attribute_id')
            ->where('ea.attribute_code = ?', ProductInterface::STATUS);

        $select = $connection->select()
            ->from(
                ['cpei' => $this->resource->getTableName('catalog_product_entity_int')], 'cpei.value'
            )->join(
                ['cpe' => $this->resource->getTableName('catalog_product_entity')],
                'cpei.entity_id = cpe.entity_id',
                []
            )->where(
                'cpei.attribute_id = ?',
                $attributeIdSelect
            )->where(
                'cpe.sku = ?',
                $sku
            );

        return (int)$connection->fetchOne($select) === Status::STATUS_DISABLED;
    }
}

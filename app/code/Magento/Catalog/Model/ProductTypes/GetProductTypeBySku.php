<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Catalog\Model\ProductTypes;

use Magento\Catalog\Api\GetProductTypeBySkuInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;

/**
 * @inheritdoc
 */
class GetProductTypeBySku implements GetProductTypeBySkuInterface
{
    /**
     * @var ProductResourceModel
     */
    private $productResource;

    /**
     * @param ProductResourceModel $productResource
     */
    public function __construct(
        ProductResourceModel $productResource
    ) {
        $this->productResource = $productResource;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku)
    {
        return $this->productResource->getProductTypeBySku($sku);
    }
}

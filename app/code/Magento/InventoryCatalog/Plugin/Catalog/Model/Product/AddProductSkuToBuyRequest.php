<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;

/**
 * Add product sku to custom option into "info_buyRequest".
 */
class AddProductSkuToBuyRequest
{
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param Json $serializer
     */
    public function __construct(
        GetSkusByProductIdsInterface $getSkusByProductIds,
        Json $serializer
    ) {
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->serializer = $serializer;
    }

    /**
     * @param Product $subject
     * @param string $code
     * @param mixed $value
     * @param int|Product $product
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException in case requested product doesn't exist.
     */
    public function beforeAddCustomOption(Product $subject, $code, $value, $product = null) :array
    {
        if ($code === 'info_buyRequest') {
            $product = $product ?: $subject;
            $sku = $this->getSkusByProductIds->execute([$product->getId()])[$product->getId()];
            $value = $this->serializer->unserialize($value);
            $value['product_sku'] = $sku;
            $value = $this->serializer->serialize($value);
        }

        return [$code, $value, $product];
    }
}

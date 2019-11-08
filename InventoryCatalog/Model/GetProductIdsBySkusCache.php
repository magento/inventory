<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @inheritdoc
 */
class GetProductIdsBySkusCache implements GetProductIdsBySkusInterface
{
    /**
     * @var GetProductIdsBySkus
     */
    private $getProductIdsBySkus;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var array
     */
    private $productIdsBySkus = [];

    /**
     * @param GetProductIdsBySkus $getProductIdsBySkus
     * @param Json $jsonSerializer
     */
    public function __construct(
        GetProductIdsBySkus $getProductIdsBySkus,
        Json $jsonSerializer
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): array
    {
        $cacheKey = $this->jsonSerializer->serialize($skus);
        if (!isset($this->productIdsBySkus[$cacheKey])) {
            $this->productIdsBySkus[$cacheKey] = $this->getProductIdsBySkus->execute($skus);
        }

        return $this->productIdsBySkus[$cacheKey];
    }
}

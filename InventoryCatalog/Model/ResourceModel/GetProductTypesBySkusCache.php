<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @inheritdoc
 */
class GetProductTypesBySkusCache implements GetProductTypesBySkusInterface
{
    /**
     * @var GetProductTypesBySkus
     */
    private $getProductTypesBySkus;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @param GetProductTypesBySkus $getProductTypesBySkus
     * @param Json $jsonSerializer
     */
    public function __construct(
        GetProductTypesBySkus $getProductTypesBySkus,
        Json $jsonSerializer
    ) {
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): array
    {
        $typesBySkus = [];
        $loadSkus = [];
        foreach ($skus as $sku) {
            $nSku = $this->normalizeSku($sku);
            if (isset($this->cache[$nSku])) {
                $typesBySkus[$sku] = $this->cache[$nSku];
            } else {
                $loadSkus[] = $sku;
                $typesBySkus[$sku] = null;
            }
        }
        if ($loadSkus) {
            $loadedTypesBySkus = $this->getProductTypesBySkus->execute($loadSkus);
            foreach ($loadedTypesBySkus as $sku => $type) {
                $typesBySkus[$sku] = $type;
                $this->cache[$this->normalizeSku($sku)] = $type;
            }
        }

        return $typesBySkus;
    }

    /**
     * Saves sku/type pair into cache
     *
     * @param string $sku
     * @param string $type
     */
    public function save(string $sku, string $type): void
    {
        $this->cache[$this->normalizeSku($sku)] = $type;
    }

    /**
     * Normalize SKU by converting it to lowercase.
     *
     * @param string $sku
     * @return string
     */
    private function normalizeSku(string $sku): string
    {
        return mb_convert_case($sku, MB_CASE_LOWER, 'UTF-8');
    }
}

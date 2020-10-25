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
    private $cache = [];

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
        $idsBySkus = [];
        $loadSkus = [];
        foreach ($skus as $sku) {
            $nSku = $this->normalizeSku($sku);
            if (isset($this->cache[$nSku])) {
                $idsBySkus[$sku] = $this->cache[$nSku];
            } else {
                $loadSkus[] = $sku;
                $idsBySkus[$sku] = null;
            }
        }
        if ($loadSkus) {
            $loadedIdsBySkus = $this->getProductIdsBySkus->execute($loadSkus);
            foreach ($loadedIdsBySkus as $sku => $id) {
                $idsBySkus[$sku] = $id;
                $this->cache[$this->normalizeSku($sku)] = $id;
            }
        }

        return $idsBySkus;
    }

    /**
     * Saves sku/id pair into cache
     *
     * @param string $sku
     * @param int $id
     */
    public function save(string $sku, int $id): void
    {
        $this->cache[$this->normalizeSku($sku)] = $id;
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

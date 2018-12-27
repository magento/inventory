<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;

/**
 * @inheritdoc
 */
class GetProductTypesBySkus implements GetProductTypesBySkusInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var string[]
     */
    private $typeBySku = [];

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
    public function execute(array $skus): array
    {
        $result = [];

        foreach ($skus as $index => $sku) {
            if (!isset($this->typeBySku[$sku])) {
                continue;
            }

            unset($skus[$index]);
            $result[$sku] = $this->typeBySku[$sku];
        }

        if (empty($skus)) {
            return $result;
        }

        $connection = $this->resource->getConnection();
        $productTable = $this->resource->getTableName('catalog_product_entity');

        $select = $connection->select()
             ->from(
                 $productTable,
                 [ProductInterface::SKU, ProductInterface::TYPE_ID]
             )->where(
ProductInterface::SKU . ' IN (?)',
                $skus
            );

        foreach ($connection->fetchPairs($select) as $sku => $productType) {
            $result[$this->getResultKey((string)$sku, $skus)] = (string)$productType;
        }

        $this->typeBySku = array_replace($this->typeBySku, $result);

        return $result;
    }

    /**
     * Return correct key for result array in GetProductTypesBySkus
     * Allows for different case sku to be passed in search array
     * with original cased sku to be passed back in result array
     *
     * @param string $sku
     * @param array $productSkuList
     * @return string
     */
    private function getResultKey(string $sku, array $productSkuList): string
    {
        $key = array_search(strtolower($sku), array_map('strtolower', $productSkuList));
        if ($key !== false) {
            $sku = (string)$productSkuList[$key];
        }
        return $sku;
    }
}

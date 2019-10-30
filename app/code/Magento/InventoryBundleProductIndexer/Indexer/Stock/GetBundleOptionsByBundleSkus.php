<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\Stock;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameResolverInterface;

/**
 * Class GetBundleOptionsByBundleSkus
 */
class GetBundleOptionsByBundleSkus
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexNameResolverInterface
     */
    private $indexNameResolver;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexNameResolverInterface $indexNameResolver
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexNameBuilder $indexNameBuilder,
        IndexNameResolverInterface $indexNameResolver,
        MetadataPool $metadataPool
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexNameResolver = $indexNameResolver;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Provides array of bundle options with simple product ids by bundle sku
     *
     * @param Collection $bundleProductsCollection
     *
     * @return array
     * @throws \Exception
     */
    public function execute(Collection $bundleProductsCollection): array
    {
        $connection = $this->resourceConnection->getConnection();
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();
        $select = $connection->select()
            ->from(
                ['bundle_selection' => $this->resourceConnection->getTableName('catalog_product_bundle_selection')],
                ['*']
            )->joinInner(
                ['bundle_product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'bundle_product.' . $linkField . '=bundle_selection.parent_product_id',
                ['sku']
            )->joinInner(
                ['bundle_option' => $this->resourceConnection->getTableName('catalog_product_bundle_option')],
                'bundle_option.parent_id=bundle_selection.parent_product_id AND bundle_option.option_id=bundle_selection.option_id',
                [
                    'is_required' => 'required',
                    'type',
                ]
            )->where(
                'bundle_product.sku IN (' . $this->getBundleProductSkusAsStringByCollection($bundleProductsCollection) . ')'
            );

        $optionData = $connection->fetchAll($select);
        $result = $this->formatOptionArray($optionData);

        return $result;
    }

    /**
     * Provide list of bundle skus as string for IN statement in sql query
     *
     * @param Collection $bundleProductsCollection
     *
     * @return string
     */
    public function getBundleProductSkusAsStringByCollection(Collection $bundleProductsCollection)
    {
        $skus = [];
        /** @var ProductInterface $product */
        foreach ($bundleProductsCollection as $product) {
            $skus[] = $product->getSku();
        }

        return '\'' . implode('\',\'', $skus) . '\'';
    }

    /**
     * @param array $optionData
     * @return array
     */
    private function formatOptionArray(array $optionData): array
    {
        $result = [];
        foreach ($optionData as $option) {
            $result[$option['sku']][$option['option_id']]['is_required'] = $option['is_required'];
            $result[$option['sku']][$option['option_id']]['type'] = $option['type'];
            $result[$option['sku']][$option['option_id']]['option_id'] = $option['option_id'];
            $result[$option['sku']][$option['option_id']]['bundle_product_id'] = $option['parent_product_id'];
            $result[$option['sku']][$option['option_id']][$option['selection_id']] = [
                'product_id' => $option['product_id'],
                'is_default' => $option['is_default'],
                'selection_qty' => $option['selection_qty'],
                'selection_can_change_qty' => $option['selection_can_change_qty'],
            ];
        }
        return $result;
    }
}

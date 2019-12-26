<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\Stock;

use Magento\Bundle\Model\LinkFactory;
use Magento\Bundle\Model\OptionFactory;
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
     * @var LinkFactory
     */
    private $optionLinkFactory;

    /**
     * @var OptionFactory
     */
    private $optionFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexNameResolverInterface $indexNameResolver
     * @param MetadataPool $metadataPool
     * @param LinkFactory $optionLinkFactory
     * @param OptionFactory $optionFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        IndexNameBuilder $indexNameBuilder,
        IndexNameResolverInterface $indexNameResolver,
        MetadataPool $metadataPool,
        LinkFactory $optionLinkFactory,
        OptionFactory $optionFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexNameResolver = $indexNameResolver;
        $this->metadataPool = $metadataPool;
        $this->optionLinkFactory = $optionLinkFactory;
        $this->optionFactory = $optionFactory;
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
                ['parent_sku' => 'sku']
            )->joinInner(
                ['child_product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'child_product.' . $linkField . '=bundle_selection.product_id',
                ['child_sku' => 'sku']
            )->joinInner(
                ['bundle_option' => $this->resourceConnection->getTableName('catalog_product_bundle_option')],
                'bundle_option.parent_id=bundle_selection.parent_product_id AND bundle_option.option_id=bundle_selection.option_id',
                [
                    'is_required' => 'required',
                    'type',
                    'option_position' => 'position'
                ]
            )->where(
                'bundle_product.sku IN (' . $this->getBundleProductSkusAsStringByCollection($bundleProductsCollection) . ')'
            );

        $optionData = $connection->fetchAll($select);

        return $this->formatToOptionsArray($optionData);
    }

    /**
     * Provide list of bundle skus as string for IN statement in sql query
     *
     * @param Collection $bundleProductsCollection
     * @return string
     */
    private function getBundleProductSkusAsStringByCollection(Collection $bundleProductsCollection)
    {
        $skus = [];
        /** @var ProductInterface $product */
        foreach ($bundleProductsCollection as $product) {
            $skus[] = $product->getSku();
        }

        return '\'' . implode('\',\'', $skus) . '\'';
    }

    /**
     * @param array $optionsData
     * @return array
     */
    private function formatToOptionsArray(array $optionsData): array
    {
        $result = [];
        foreach ($optionsData as $optionData) {
            $optionLink = $this->optionLinkFactory->create(
                [
                    'data' => [
                        'id' => $optionData['selection_id'],
                        'option_id' => $optionData['option_id'],
                        'position' => $optionData['position'],
                        'price_type' => $optionData['selection_price_type'],
                        'price' => $optionData['selection_price_value'],
                        'is_default' => $optionData['is_default'],
                        'sku' => $optionData['child_sku'],
                        'qty' => $optionData['selection_qty'],
                        'can_change_qty' => $optionData['selection_can_change_qty']
                    ]
                ]
            );
            if (!isset($result[$optionData['parent_sku']][$optionData['option_id']])) {
                $option = $this->optionFactory->create(
                    [
                        'data' => [
                            'option_id' => $optionData['option_id'],
                            'required' => $optionData['is_required'],
                            'type' => $optionData['type'],
                            'position' => $optionData['option_position'],
                            'sku' => $optionData['parent_sku']
                        ]
                    ]
                );
                $result[$optionData['parent_sku']][$optionData['option_id']] = $option;
            }
            $productLinks = $result[$optionData['parent_sku']][$optionData['option_id']]->getProductLinks() ?? [];
            $result[$optionData['parent_sku']][$optionData['option_id']]->setProductLinks(
                array_merge($productLinks, [$optionLink])
            );
        }

        return $result;
    }
}

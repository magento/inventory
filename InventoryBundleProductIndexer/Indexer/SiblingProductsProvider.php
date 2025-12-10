<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\InventoryIndexer\Indexer\SiblingProductsProviderInterface;

class SiblingProductsProvider implements SiblingProductsProviderInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly MetadataPool $metadataPool,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getSkus(array $skus): array
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['child_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )->joinInner(
                ['sibling_link' => $this->resourceConnection->getTableName('catalog_product_bundle_selection')],
                'sibling_link.product_id = child_product_entity.' . $metadata->getIdentifierField(),
                []
            )->joinInner(
                ['sibling_product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'sibling_product_entity.' . $metadata->getLinkField() . ' = sibling_link.parent_product_id',
                ['sku' => 'sibling_product_entity.sku']
            )->where(
                'child_product_entity.sku IN (?)',
                $skus
            );
        $siblingSkus = $connection->fetchCol($select);

        return $siblingSkus;
    }
}

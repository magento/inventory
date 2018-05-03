<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Patch\Schema;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Creates MySQL View to use when Default Stock is used.
 */
class CreateLegacyStockStatusView implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param SchemaSetupInterface $schemaSetup
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        DefaultStockProviderInterface $defaultStockProvider,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        MetadataPool $metadataPool
    ) {
        $this->schemaSetup = $schemaSetup;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $defaultStockId = $this->defaultStockProvider->getId();
        $legacyView = $this->stockIndexTableNameResolver->execute($defaultStockId);
        $cataloginventoryStockStatus = $this->schemaSetup->getTable('cataloginventory_stock_status');
        $catalogProductEntity = $this->schemaSetup->getTable('catalog_product_entity');
        $sql = "CREATE
                VIEW {$legacyView}
                  AS
                    SELECT
                      css.product_id,
                      css.website_id,
                      css.stock_id,
                      css.qty          AS quantity,
                      css.stock_status AS is_salable,
                      cpe.sku
                    FROM {$cataloginventoryStockStatus} AS css
                      INNER JOIN {$catalogProductEntity} AS cpe
                        ON css.product_id = cpe.{$linkField};";
        $this->schemaSetup->getConnection()->query($sql);
        $this->schemaSetup->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}

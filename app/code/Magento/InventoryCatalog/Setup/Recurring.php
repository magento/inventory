<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * @codeCoverageIgnore
 */
class Recurring implements InstallSchemaInterface
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        MetadataPool $metadataPool
    ) {
        $this->metadataPool = $metadataPool;
    }

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $this->dropForeignKey($installer);

        $installer->endSetup();
    }

    /**
     * Drop foreign key
     *
     * @param SchemaSetupInterface $setup
     * @return void
     * @throws \Exception
     */
    protected function dropForeignKey(SchemaSetupInterface $setup)
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $foreignKeys = $this->getForeignKeys(
            $setup,
            'cataloginventory_stock_item',
            'product_id',
            $metadata->getEntityTable(),
            $metadata->getIdentifierField()
        );
        foreach ($foreignKeys as $foreignKey) {
            $setup->getConnection()->dropForeignKey(
                $foreignKey['TABLE_NAME'],
                $foreignKey['FK_NAME']
            );
        }
    }

    /**
     * Get foreign keys for tables and columns
     *
     * @param SchemaSetupInterface $setup
     * @param string $targetTable
     * @param string $targetColumn
     * @param string $refTable
     * @param string $refColumn
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function getForeignKeys(
        SchemaSetupInterface $setup,
        $targetTable,
        $targetColumn,
        $refTable,
        $refColumn
    ) {
        $foreignKeys = $setup->getConnection()->getForeignKeys(
            $setup->getTable($targetTable)
        );
        $foreignKeys = array_filter(
            $foreignKeys,
            function ($key) use ($targetColumn, $refTable, $refColumn) {
                return $key['COLUMN_NAME'] == $targetColumn
                    && $key['REF_TABLE_NAME'] == $refTable;
            }
        );
        return $foreignKeys;
    }
}

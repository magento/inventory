<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Bundle\Model\Product\Type as BundleProductType;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Indexer\SiblingSelectBuilderInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexAlias;

/**
 * Get bundle product for given stock select builder.
 */
class SelectBuilder implements SiblingSelectBuilderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var OptionsStatusSelectBuilder
     */
    private $optionsStatusSelectBuilder;

    /**
     * @param ResourceConnection $resourceConnection
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param OptionsStatusSelectBuilder $optionsStatusSelectBuilder
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        DefaultStockProviderInterface $defaultStockProvider,
        OptionsStatusSelectBuilder $optionsStatusSelectBuilder
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->optionsStatusSelectBuilder = $optionsStatusSelectBuilder;
    }

    /**
     * @inheritdoc
     */
    public function getSelect(int $stockId, array $skuList = [], IndexAlias $indexAlias = IndexAlias::MAIN): Select
    {
        $connection = $this->resourceConnection->getConnection();

        $optionsStatusSelect = $this->optionsStatusSelectBuilder->execute($stockId, $skuList, $indexAlias);
        $isRequiredOptionUnavailable = $connection->getCheckSql(
            'options.required AND options.stock_status = 0',
            '1',
            '0'
        );
        $select = $connection->select()
            ->from(
                ['product_entity' => $this->resourceConnection->getTableName('catalog_product_entity')],
                []
            )->joinLeft(
                ['options' => $optionsStatusSelect],
                'options.sku = product_entity.sku',
                []
            )->joinLeft(
                ['legacy_stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                'legacy_stock_item.product_id = product_entity.entity_id'
                . ' AND legacy_stock_item.stock_id = ' . $this->defaultStockProvider->getId(),
                []
            )->where(
                'product_entity.type_id = ?',
                BundleProductType::TYPE_CODE
            )->group(
                ['product_entity.sku']
            )->columns([
                IndexStructure::SKU => 'product_entity.sku',
                IndexStructure::QUANTITY => $connection->getIfNullSql('SUM(options.quantity)', '0'),
                IndexStructure::IS_SALABLE => $connection->getCheckSql(
                    'legacy_stock_item.is_in_stock = 0 OR options.sku IS NULL',
                    '0',
                    'MAX(' . $isRequiredOptionUnavailable . ') = 0 AND MAX(options.stock_status) = 1'
                ),
            ]);

        if (!empty($skuList)) {
            $select->where('product_entity.sku IN (?)', $skuList);
        }

        return $select;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Inventory\Model\ResourceModel\Source;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\Inventory\Model\SourceItem as SourceItemModel;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;
use Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LowQuantityCollection extends AbstractCollection
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var GetAllowedProductTypesForSourceItemManagementInterface
     */
    private $getAllowedProductTypesForSourceItemManagement;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var int
     */
    private $filterStoreId;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param AttributeRepositoryInterface $attributeRepository
     * @param StockConfigurationInterface $stockConfiguration
     * @param GetAllowedProductTypesForSourceItemManagementInterface $getAllowedProductTypesForSourceItemManagement
     * @param MetadataPool $metadataPool
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        AttributeRepositoryInterface $attributeRepository,
        StockConfigurationInterface $stockConfiguration,
        GetAllowedProductTypesForSourceItemManagementInterface $getAllowedProductTypesForSourceItemManagement,
        MetadataPool $metadataPool,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );

        $this->attributeRepository = $attributeRepository;
        $this->stockConfiguration = $stockConfiguration;
        $this->getAllowedProductTypesForSourceItemManagement = $getAllowedProductTypesForSourceItemManagement;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(SourceItemModel::class, SourceItemResourceModel::class);

        $this->addFilterToMap('source_code', 'main_table.source_code');
        $this->addFilterToMap('sku', 'main_table.sku');
        $this->addFilterToMap('product_name', 'product_entity_varchar.value');
    }

    /**
     * Set store id to filter.
     *
     * @param int $storeId
     * @return void
     */
    public function addStoreFilter(int $storeId)
    {
        $this->filterStoreId = $storeId ? [$storeId] : $storeId;
    }

    /**
     * Set store ids to filter.
     *
     * @param array $storeIds
     * @return void
     */
    public function addStoresFilter(array $storeIds)
    {
        $this->filterStoreId = $storeIds;
    }

    /**
     * @inheritdoc
     */
    protected function _renderFilters()
    {
        if (false === $this->_isFiltersRendered) {
            $this->joinInventoryConfiguration();
            $this->joinCatalogProduct();

            $this->addProductTypeFilter();
            $this->addNotifyStockQtyFilter();
            $this->addEnabledSourceFilter();
            $this->addSourceItemInStockFilter();
            $this->addSourceItemStoreFilter();
        }
        return parent::_renderFilters();
    }

    /**
     * @inheritdoc
     */
    protected function _renderOrders()
    {
        if (false === $this->_isOrdersRendered) {
            $this->setOrder(SourceItemInterface::QUANTITY, self::SORT_ORDER_ASC);
        }
        return parent::_renderOrders();
    }

    /**
     * JoinCatalogProduct depends on dynamic condition 'filterStoreId'
     *
     * @return void
     * @throws NoSuchEntityException
     */
    private function joinCatalogProduct(): void
    {
        $productEntityTable = $this->getTable('catalog_product_entity');
        $productEavVarcharTable = $this->getTable('catalog_product_entity_varchar');
        $productEavIntTable = $this->getTable('catalog_product_entity_int');
        $nameAttribute = $this->attributeRepository->get('catalog_product', 'name');
        $statusAttribute = $this->attributeRepository->get('catalog_product', 'status');
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();

        $this->getSelect()->joinInner(
            ['product_entity' => $productEntityTable],
            'main_table.' . SourceItemInterface::SKU . ' = product_entity.' . ProductInterface::SKU,
            []
        );

        $this->getSelect()->joinInner(
            ['product_entity_varchar' => $productEavVarcharTable],
            'product_entity_varchar.' . $linkField . ' = product_entity.' . $linkField . ' ' .
            'AND product_entity_varchar.store_id = ' . Store::DEFAULT_STORE_ID . ' ' .
            'AND product_entity_varchar.attribute_id = ' . (int)$nameAttribute->getAttributeId(),
            []
        );

        $this->getSelect()->joinInner(
            ['product_entity_int' => $productEavIntTable],
            'product_entity_int.' . $linkField . ' = product_entity.' . $linkField . ' ' .
            'AND product_entity_int.attribute_id = ' . (int)$statusAttribute->getAttributeId()
            . ' AND product_entity_int.store_id = ' . Store::DEFAULT_STORE_ID,
            []
        );

        if (null !== $this->filterStoreId) {
            $productEavVarcharCondition = [
                'product_entity_varchar_store.' . $linkField . ' = product_entity.' . $linkField,
                $this->getConnection()->quoteInto(
                    'product_entity_varchar_store.store_id IN (?)',
                    $this->filterStoreId,
                    \Zend_Db::INT_TYPE
                ),
                $this->getConnection()->quoteInto(
                    'product_entity_varchar_store.attribute_id = ?',
                    (int)$nameAttribute->getAttributeId(),
                    \Zend_Db::INT_TYPE
                )
            ];
            $this->getSelect()->joinLeft(
                ['product_entity_varchar_store' => $productEavVarcharTable],
                join(' AND ', $productEavVarcharCondition),
                [
                    'product_name' => $this->getConnection()->getIfNullSql(
                        'product_entity_varchar_store.value',
                        'product_entity_varchar.value'
                    ),
                ]
            );

            $productEavIntCondition = [
                'product_entity_int_store.' . $linkField . ' = product_entity.' . $linkField,
                $this->getConnection()->quoteInto(
                    'product_entity_int_store.attribute_id = ?',
                    (int)$statusAttribute->getAttributeId(),
                    \Zend_Db::INT_TYPE
                ),
                $this->getConnection()->quoteInto(
                    'product_entity_int_store.store_id IN (?)',
                    $this->filterStoreId,
                    \Zend_Db::INT_TYPE
                )
            ];
            $this->getSelect()->joinLeft(
                ['product_entity_int_store' => $productEavIntTable],
                join(' AND ', $productEavIntCondition),
                []
            )->where(
                $this->getConnection()->getIfNullSql(
                    'product_entity_int_store.value',
                    'product_entity_int.value'
                ) . '= ?',
                Status::STATUS_ENABLED
            );
        } else {
            $this->getSelect()->columns(['product_name' => 'product_entity_varchar.value'])
                ->where('product_entity_int.value = ?', Status::STATUS_ENABLED);
        }
    }

    /**
     * Add inventory configuration information to collection.
     *
     * @return void
     */
    private function joinInventoryConfiguration(): void
    {
        $sourceItemConfigurationTable = $this->getTable('inventory_low_stock_notification_configuration');

        $this->getSelect()->joinInner(
            ['notification_configuration' => $sourceItemConfigurationTable],
            sprintf(
                'main_table.%s = notification_configuration.%s AND main_table.%s = notification_configuration.%s',
                SourceItemInterface::SKU,
                SourceItemConfigurationInterface::SKU,
                SourceItemInterface::SOURCE_CODE,
                SourceItemConfigurationInterface::SOURCE_CODE
            ),
            []
        );
    }

    /**
     * Filter allowed product types.
     *
     * @return void
     */
    private function addProductTypeFilter(): void
    {
        $this->addFieldToFilter(
            'product_entity.type_id',
            $this->getAllowedProductTypesForSourceItemManagement->execute()
        );
    }

    /**
     * Add notify configuration information to collection.
     *
     * @return void
     */
    private function addNotifyStockQtyFilter(): void
    {
        $notifyStockExpression = $this->getConnection()->getIfNullSql(
            'notification_configuration.' . SourceItemConfigurationInterface::INVENTORY_NOTIFY_QTY,
            (float)$this->stockConfiguration->getNotifyStockQty()
        );

        $this->getSelect()->where(
            SourceItemInterface::QUANTITY . ' < ?',
            $notifyStockExpression
        );
    }

    /**
     * Filter disabled sources.
     *
     * @return void
     */
    private function addEnabledSourceFilter(): void
    {
        $this->getSelect()->joinInner(
            ['inventory_source' => $this->getTable(Source::TABLE_NAME_SOURCE)],
            sprintf(
                'inventory_source.%s = 1 AND inventory_source.%s = main_table.%s',
                SourceInterface::ENABLED,
                SourceInterface::SOURCE_CODE,
                SourceItemInterface::SOURCE_CODE
            ),
            []
        );
    }

    /**
     * Filter out of stock source items.
     *
     * @return void
     */
    private function addSourceItemInStockFilter(): void
    {
        $condition = '(' . SourceItemInterface::QUANTITY . ' > 0 AND main_table.status = ' .
            SourceItemInterface::STATUS_IN_STOCK . ')';
        $this->getSelect()->where($condition);
    }

    /**
     * Filter source items by store if provided.
     *
     * @return void
     */
    private function addSourceItemStoreFilter(): void
    {
        if ($this->filterStoreId === null) {
            return;
        }

        $storeCondition = [
            'store.website_id = website.website_id',
            $this->getConnection()->quoteInto(
                'store.store_id IN (?)',
                $this->filterStoreId,
                \Zend_Db::INT_TYPE
            )
        ];
        $this->getSelect()->joinInner(
            ['source_stock_link' => $this->getTable('inventory_source_stock_link')],
            'source_stock_link.source_code = inventory_source.source_code',
            []
        )->joinInner(
            ['stock' => $this->getTable('inventory_stock')],
            'stock.stock_id = source_stock_link.stock_id',
            []
        )->joinInner(
            ['sales_channel' => $this->getTable('inventory_stock_sales_channel')],
            'sales_channel.stock_id = stock.stock_id',
            []
        )->joinInner(
            ['website' => $this->getTable('store_website')],
            'website.code = sales_channel.code and sales_channel.type = "website"',
            []
        )->joinInner(
            ['store' => $this->getTable('store')],
            join(' AND ', $storeCondition),
            []
        );
    }
}

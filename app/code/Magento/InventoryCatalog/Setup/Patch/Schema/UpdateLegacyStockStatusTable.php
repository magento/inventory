<?php
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\DB\Ddl\TriggerFactory;

/**
 * Optimization for MySQL View Default Stock
 */
class UpdateLegacyStockStatusTable implements SchemaPatchInterface
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
     * @var TriggerFactory
     */
    private $triggerFactory;

    /**
     * @var array
     */
    protected $triggers = [
        Trigger::EVENT_INSERT => Trigger::TIME_AFTER,
        Trigger::EVENT_UPDATE => Trigger::TIME_AFTER,
        Trigger::EVENT_DELETE => Trigger::TIME_AFTER,
    ];

    /**
     * @param SchemaSetupInterface                 $schemaSetup
     * @param DefaultStockProviderInterface        $defaultStockProvider
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param TriggerFactory                       $triggerFactory
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        DefaultStockProviderInterface $defaultStockProvider,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        TriggerFactory $triggerFactory
    ) {
        $this->schemaSetup = $schemaSetup;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->triggerFactory = $triggerFactory;
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
        $defaultStockId = $this->defaultStockProvider->getId();
        $viewToLegacyIndex = $this->stockIndexTableNameResolver->execute($defaultStockId);
        $legacyStockStatusTable = $this->schemaSetup->getTable('cataloginventory_stock_status');
        $connection = $this->schemaSetup->getConnection();

        // Check if there exists a view for the legacy stock status.
        $legacyStockView = $connection->query($this->_fetchLegacyViewQuery(), [$viewToLegacyIndex])->fetch();
        if ($legacyStockView) {
            // Drop old version of view
            $connection->query("DROP VIEW {$viewToLegacyIndex}");
            // Create legacy table index
            $this->_createLegacyIndexTable($connection, $viewToLegacyIndex);
            // Copy data from legacy table
            $this->_copyLegacyData($legacyStockStatusTable, $viewToLegacyIndex, $connection);
        }
        $this->schemaSetup->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
            CreateLegacyStockStatusView::class,
        ];
    }

    /**
     * @param string           $targetTable
     * @param string           $subscriptionTable
     * @param AdapterInterface $connection
     *
     * @throws \Zend_Db_Exception
     */
    protected function _createTriggers($targetTable, $subscriptionTable, $connection)
    {
        foreach ($this->triggers as $event => $trigger) {
            $trigger = $this->triggerFactory->create()
                ->setName($subscriptionTable . '_' . $trigger . '_' . $event)
                ->setTime($trigger)
                ->setEvent($event)
                ->setTable($connection->getTableName($subscriptionTable));

            $trigger->addStatement($this->_buildTriggerStatement($targetTable, $event));

            $connection->dropTrigger($trigger->getName());
            $connection->createTrigger($trigger);
        }
    }

    /**
     * @param string $targetTable
     * @param string $event
     *
     * @return string
     */
    protected function _buildTriggerStatement($targetTable, $event)
    {
        switch ($event) {
            case Trigger::EVENT_INSERT:
            case Trigger::EVENT_UPDATE:
                return "INSERT INTO {$targetTable} VALUES(NEW.product_id, NEW.website_id, NEW.stock_id, NEW.qty, NEW.stock_status, (SELECT sku FROM catalog_product_entity WHERE entity_id = NEW.product_id)) ON DUPLICATE KEY UPDATE quantity = NEW.qty, is_salable = NEW.stock_status;";

            case Trigger::EVENT_DELETE:
                return "DELETE FROM {$targetTable} WHERE product_id = OLD.product_id AND  website_id = OLD.website_id AND stock_id = OLD.stock_id;";
        }
    }

    /**
     * @param string           $legacyTable
     * @param string           $legacyIndexTable
     * @param AdapterInterface $connection
     */
    protected function _copyLegacyData($legacyTable, $legacyIndexTable, $connection)
    {
        $select = $connection->select()
            ->from(
                [
                    'inventory_stock_status' => $legacyTable,
                ],
                [
                    'product_id',
                    'website_id',
                    'stock_id',
                    'qty',
                    'stock_status',
                    'sku',
                ]
            )->join(
                ['catalog_product_entity' => $connection->getTableName('catalog_product_entity')],
                'catalog_product_entity.entity_id = inventory_stock_status.product_id'
            );

        $select->insertFromSelect(
            $legacyIndexTable
        );
    }

    /**
     * @param AdapterInterface $connection
     * @param string           $tableName
     *
     * @throws \Zend_Db_Exception
     */
    protected function _createLegacyIndexTable($connection, $tableName)
    {
        $table = $connection->newTable($tableName)
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                10,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ],
                'Product ID'
            )
            ->addColumn(
                'website_id',
                Table::TYPE_SMALLINT,
                5,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )->addColumn(
                'stock_id',
                Table::TYPE_SMALLINT,
                5,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )->addColumn(
                'quantity',
                Table::TYPE_DECIMAL,
                [
                    12,
                    4,
                ],
                [
                    'nullable' => false,
                    'default' => '0.0000',
                ]
            )->addColumn(
                'is_salable',
                Table::TYPE_SMALLINT,
                5,
                [
                    'unsigned' => true,
                    'nullable' => false,
                ]
            )->addColumn(
                'sku',
                Table::TYPE_TEXT,
                64,
                [
                    'nullable' => false,
                ]
            )
            ->setComment('Cataloginventory Stock')
            ->setOption('charset', 'utf8')
            ->setOption('type', 'InnoDB')
            ->addIndex(
                $connection->getIndexName(
                    $tableName,
                    [
                        'product_id',
                        'website_id',
                        'stock_id',
                    ],
                    AdapterInterface::INDEX_TYPE_PRIMARY
                ),
                [
                    'product_id',
                    'website_id',
                    'stock_id',
                ],
                ['type' => AdapterInterface::INDEX_TYPE_PRIMARY]
            )->addIndex(
                $connection->getIndexName($tableName, ['stock_id']),
                ['stock_id']
            )->addIndex(
                $connection->getIndexName($tableName, ['website_id']),
                ['website_id']
            )->addIndex(
                $connection->getIndexName($tableName, ['is_salable']),
                ['is_salable']
            )->addIndex(
                $connection->getIndexName($tableName, ['sku']),
                ['sku']
            );

        $connection->createTable($table);
    }

    /**
     * @return string string
     */
    protected function _fetchLegacyViewQuery()
    {
        return "
        SELECT  1
            FROM    information_schema . views
            WHERE   TABLE_NAME = ?
        ";
    }
}

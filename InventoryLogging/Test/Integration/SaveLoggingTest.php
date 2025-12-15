<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLogging\Test\Integration;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\DB\Adapter\TableNotFoundException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class SaveLoggingTest extends AbstractBackendController
{
    /**
     * @var ObjectManagerInterface
     */
    private ObjectManagerInterface $objectManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->resource = $this->objectManager->get(ProductResource::class);
    }

    /**
     * Verifies that saving a product (changing qty via POST) writes a logging row
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoAppArea adminhtml
     * @return void
     */
    public function testLoggingHasSourceItemIdWhenQtyIsChangedThroughProductSave(): void
    {
        /** @var Manager $moduleManager */
        $moduleManager = $this->objectManager->get(Manager::class);
        if (!$moduleManager->isEnabled('Magento_Logging')) {
            self::markTestSkipped('Magento_Logging module disabled.');
        }

        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($this->getProductPostDetails());

        $this->dispatch('backend/catalog/product/save');

        $latestInfo = $this->getLatestLoggingInfo();
        $this->assertStringContainsString(
            'source_item_id',
            $latestInfo,
            "Expected latest logging event info to contain 'source_item_id', got: {$latestInfo}"
        );
    }

    /**
     * Returns POST details for saving a simple product
     *
     * @return array[]
     */
    private function getProductPostDetails(): array
    {
        $optionsContainerDefault = $this->resource->getAttribute('options_container')->getDefaultValue();
        return [
            'product' => [
                'type' => 'simple',
                'sku' => 'simple',
                'store' => '0',
                'set' => '4',
                'back' => 'edit',
                'product' => [],
                'type_id' => Type::TYPE_SIMPLE,
                'is_downloadable' => '0',
                'affect_configurable_product_attributes' => '1',
                'new_variation_attribute_set_id' => '4',
                'use_default' => [
                    'gift_message_available' => '0',
                    'gift_wrapping_available' => '0'
                ],
                'configurable_matrix_serialized' => '[]',
                'associated_product_ids_serialized' => '[]',
                'options_container' => $optionsContainerDefault,
                'sources' => [
                    'assigned_sources' => [
                        'source_code' => 'default',
                        'name' => 'Default Source',
                        'quantity' => '1',
                        'source_status' => '1',
                        'notify_stock_qty' => '1',
                        'notify_stock_qty_use_default' => '1',
                        'position' => '1',
                        'record_id' => 'default',
                        'status' => '1'
                    ],
                    'assign_sources_grid' => [
                        'source_code' => 'default'
                    ]
                ]
            ]
        ];
    }

    /**
     * Fetches the `info` column of the latest row in `magento_logging_event` table
     *
     * @return string
     */
    private function getLatestLoggingInfo(): string
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTable('magento_logging_event');

        $select = $connection->select()
            ->from($tableName, ['info'])
            ->order('log_id DESC')
            ->limit(1);
        $result = $connection->fetchOne($select);

        return is_string($result) ? $result : '';
    }
}

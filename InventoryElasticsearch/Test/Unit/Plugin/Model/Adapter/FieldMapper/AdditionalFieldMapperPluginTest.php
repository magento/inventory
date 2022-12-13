<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Test\Unit\Plugin\Model\Adapter\FieldMapper;

use Magento\Elasticsearch\Elasticsearch5\Model\Adapter\FieldMapper\ProductFieldMapper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryElasticsearch\Plugin\Model\Adapter\FieldMapper\AdditionalFieldMapperPlugin;
use PHPUnit\Framework\TestCase;

/**
 * Test class for getting additional sorting attribute for product plugin
 */
class AdditionalFieldMapperPluginTest extends TestCase
{
    /**
     * @var AdditionalFieldMapperPlugin
     */
    private $plugin;

    /**
     * @var ProductFieldMapper
     */
    private $productFieldMapperMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->productFieldMapperMock = $this->createMock(ProductFieldMapper::class);

        $this->plugin = (new ObjectManager($this))->getObject(
            AdditionalFieldMapperPlugin::class
        );
    }

    /**
     * Test for `afterGetAllAttributesTypes` to add additional sorting attribute
     *
     * @return void
     */
    public function testAfterGetAllAttributesTypes(): void
    {
        $exitingAttributes = [
            'activity' => ['type' => 'keyword'],
            'color' => ['type' => 'integer']
        ];
        $additionalAttribute = ['is_out_of_stock' => ['type' => 'integer']];
        $expectedResult = array_merge($exitingAttributes, $additionalAttribute);

        $this->assertSame(
            $expectedResult,
            $this->plugin->afterGetAllAttributesTypes($this->productFieldMapperMock, $exitingAttributes, [])
        );
    }
}

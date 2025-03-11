<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Test\Integration\Plugin\CatalogInventory\Model\System\Config\Backend\Minqty;

use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventory\Model\System\Config\Backend\Minqty;
use Magento\Config\Model\Config\BackendFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks that min_qty config can be assigned a value below 0
 *
 * @magentoAppArea adminhtml
 */
class AllowNegativeMinQtyInConfigPluginTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var Minqty */
    private $minQty;

    /** @var BackendFactory */
    private $backendFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->backendFactory = $this->objectManager->create(BackendFactory::class);
        $this->minQty = $this->backendFactory->create(Minqty::class, [
            'data' => [
                'path' => Configuration::XML_PATH_MIN_QTY,
            ],
        ]);
    }

    /**
     * @dataProvider beforeSaveDataProvider
     * @magentoConfigFixture default/cataloginventory/item_options/min_qty 1
     * @param string $value
     * @param string $expectedMinQty
     * @return void
     */
    public function testBeforeSave(string $value, string $expectedMinQty): void
    {
        $this->minQty->addData([
            'value' => $value,
            'fieldset_data' => [
                'min_qty' => $value,
            ],
        ]);
        $this->minQty->beforeSave();

        $this->assertEquals($expectedMinQty, $this->minQty->getValue());
    }

    /**
     * Data provider for testBeforeSave
     *
     * @return array
     */
    public static function beforeSaveDataProvider(): array
    {
        return [
            'min_qty_positive' => [
                'value' => '5',
                'expectedMinQty' => '5',
            ],
            'min_qty_negative' => [
                'value' => '-5',
                'expectedMinQty' => '-5',
            ],
        ];
    }
}

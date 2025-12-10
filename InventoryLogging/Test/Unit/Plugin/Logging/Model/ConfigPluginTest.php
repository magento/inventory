<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLogging\Test\Unit\Plugin\Logging\Model;

use Magento\InventoryLogging\Plugin\Logging\Model\ConfigPlugin;
use Magento\Inventory\Model\SourceItem;
use Magento\Logging\Model\Config;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class ConfigPluginTest extends TestCase
{
    /**
     * @var ConfigPlugin
     */
    private ConfigPlugin $configPlugin;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->configPlugin = new ConfigPlugin();
        parent::setUp();
    }

    /**
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Config::class)) {
            self::markTestSkipped('Magento_Logging module not available.');
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testAfterGetEventGroupConfigWithAffectedGroupName(): void
    {
        $subject = $this->createMock(Config::class);
        $result = [
            'expected_models' => [
                SourceItem::class => []
            ]
        ];
        $groupName = 'catalog_products';

        $actual = $this->configPlugin->afterGetEventGroupConfig($subject, $result, $groupName);

        $this->assertArrayHasKey('additional_data', $actual['expected_models'][SourceItem::class]);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testAfterGetEventGroupConfigReturnsUnchangedResultForNonAffectedGroup(): void
    {
        $subject = $this->createMock(Config::class);
        $result = ['expected_models' => []];
        $groupName = 'unrelated_group';

        $actual = $this->configPlugin->afterGetEventGroupConfig($subject, $result, $groupName);

        $this->assertSame($result, $actual);
    }
}

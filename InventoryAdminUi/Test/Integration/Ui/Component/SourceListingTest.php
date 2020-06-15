<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Test\Integration\Ui\Component;

use Magento\Backend\Model\Auth;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Layout\Data\Structure;
use Magento\Framework\View\Layout\Generator\ContextFactory as GeneratorContextFactory;
use Magento\Framework\View\Layout\Generator\UiComponent;
use Magento\Framework\View\Layout\Reader\ContextFactory as ReaderContextFactory;
use Magento\Framework\View\Layout\ScheduledStructure;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for \Magento\InventoryAdminUi\Ui\Component\Listing\MassAction
 *
 * @magentoAppArea adminhtml
 */
class SourceListingTest extends TestCase
{
    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var ReaderContextFactory
     */
    private $readerContextFactory;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var GeneratorContextFactory
     */
    private $generatorContextFactory;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var UiComponent
     */
    private $uiComponent;

    /**
     * @var ScheduledStructure
     */
    private $scheduledStructure;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->auth = $objectManager->get(Auth::class);
        $this->readerContextFactory = $objectManager->get(ReaderContextFactory::class);
        $this->structure = $objectManager->get(Structure::class);
        $this->layout = $objectManager->get(LayoutInterface::class);
        $this->generatorContextFactory = $objectManager->get(GeneratorContextFactory::class);
        $this->uiComponent = $objectManager->get(UiComponent::class);
        $this->scheduledStructure = $objectManager->get(ScheduledStructure::class);

        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        $this->auth->logout();
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testPrepareUserHasAllPermissions(): void
    {
        $this->auth->login(
            \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        $resultBlock = $this->processUiComponent();
        $this->assertStringContainsString('"Enable"', $resultBlock->toHtml());
        $this->assertStringContainsString('"Edit","hidden":false', $resultBlock->toHtml());
    }

    /**
     * @magentoDataFixture Magento_InventoryAdminUi::Test/Integration/_files/user_with_restricted_role.php
     *
     * @return void
     */
    public function testPrepareUserWithRestrictedRole(): void
    {
        $this->auth->login(
            'sourceAccessUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );
        $resultBlock = $this->processUiComponent();
        $this->assertStringNotContainsString('"Enable"', $resultBlock->toHtml());
        $this->assertStringContainsString('"Edit","hidden":true', $resultBlock->toHtml());
    }

    /**
     * @return BlockInterface
     */
    private function processUiComponent(): BlockInterface
    {
        $this->scheduledStructure->setElement(
            'inventory_source_listing',
            [
                'uiComponent',
                [
                    'attributes' => [
                        'group' => '',
                        'component' => '',
                        'aclResource' => '',
                        'visibilityConditions' => [],
                    ],
                ],
            ]
        );

        $readerContext = $this->readerContextFactory->create(
            ['scheduledStructure' => $this->scheduledStructure]
        );

        $generatorContext = $this->generatorContextFactory->create(
            [
                'structure' => $this->structure,
                'layout' => $this->layout,
            ]
        );

        $this->uiComponent->process($readerContext, $generatorContext);
        $resultBlock = $this->layout->getBlock('inventory_source_listing');

        return $resultBlock;
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Test\Integration\Controller\Adminhtml\Source;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Message\MessageInterface;
use Magento\InventoryAdminUi\Controller\Adminhtml\Source\Save;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * Verify Source Save controller processes and saves request data as Source entity correctly.
 *
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    /**
     * Test subject.
     *
     * @var Save
     */
    private $controller;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = $this->_objectManager->get(Save::class);
    }

    /**
     * Verify, source will be saved with region id and region name if both supplied in request.
     *
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/910317/scenarios/3334660
     * @return void
     */
    public function testExecute(): void
    {
        $requestData = $this->getRequestData();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($requestData);
        $this->dispatch('backend/inventory/source/save');
        $source = $this->_objectManager->get(SourceRepositoryInterface::class)
            ->get('test_source_with_region_id_and_region');
        $this->assertEquals('test_source_with_region_id_and_region', $source->getSourceCode());
        $this->assertEquals('Ain', $source->getRegion());
        $this->assertEquals('182', $source->getRegionId());
    }

    /**
     * Verify, source will not be saved with name that already exists in database.
     *
     * @return void
     */
    public function testValidateUniqueName(): void
    {
        $requestData = $this->getRequestData();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($requestData);
        $this->dispatch('backend/inventory/source/save');
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $requestData['general']['source_code'] .= '_new';
        $this->getRequest()->setPostValue($requestData);
        $this->dispatch('backend/inventory/source/save');
        $this->assertSessionMessages($this->equalTo(['Could not save Source']), MessageInterface::TYPE_ERROR);
        $this->assertRedirect($this->stringContains('inventory/source/new'));
    }

    /**
     * Data for test.
     *
     * @return array
     */
    private function getRequestData(): array
    {
        return [
            'general' => [
                'source_code' => 'test_source_with_region_id_and_region',
                'name' => 'Test Source With Region ID And Region',
                'latitude' => '',
                'longitude' => '',
                'contact_name' => '',
                'email' => '',
                'phone' => '',
                'fax' => '',
                'region' => 'Ain',
                'city' => '',
                'street' => '',
                'postcode' => '12345',
                'enabled' => '1',
                'description' => '',
                'country_id' => 'FR',
                'region_id' => '182',
            ],
        ];
    }

    /**
     * Verify, source will not be saved with source code that already exists in database.
     *
     * @return void
     */
    public function testValidateUniqueCode(): void
    {
        $requestData = $this->getRequestData();
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue($requestData);
        $this->dispatch('backend/inventory/source/save');
        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $requestData['general']['name'] .= '_new';
        $this->getRequest()->setPostValue($requestData);
        $this->dispatch('backend/inventory/source/save');
        $this->assertSessionMessages($this->equalTo(['Could not save Source.']), MessageInterface::TYPE_ERROR);
        $this->assertRedirect($this->stringContains('inventory/source/new'));
    }
}

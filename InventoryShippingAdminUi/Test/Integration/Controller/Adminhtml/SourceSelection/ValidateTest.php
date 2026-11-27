<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Test\Integration\Controller\Adminhtml\SourceSelection;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\TestFramework\TestCase\AbstractBackendController;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test the source selection validation that runs before a shipment is created
 *
 * @magentoAppArea adminhtml
 */
class ValidateTest extends AbstractBackendController
{
    /**
     * The form posts the item flags the way jQuery serializes them, so booleans arrive as strings.
     *
     * @param string $isManageStock
     * @param bool $expectedError
     * @return void
     */
    #[DataProvider('itemsDataProvider')]
    public function testExecute(string $isManageStock, bool $expectedError): void
    {
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(
            [
                'sourceCode' => 'default',
                'items' => [
                    [
                        'sku' => 'simple',
                        'qtyToShip' => '1',
                        'isManageStock' => $isManageStock,
                        'sources' => [
                            ['sourceCode' => 'default', 'qtyAvailable' => '0', 'qtyToDeduct' => '1'],
                        ],
                    ],
                ],
            ]
        );
        $this->dispatch('backend/inventoryshipping/sourceselection/validate');

        $response = $this->_objectManager->get(Json::class)->unserialize($this->getResponse()->getBody());
        $this->assertSame($expectedError, $response['error']);
    }

    /**
     * @return array
     */
    public static function itemsDataProvider(): array
    {
        return [
            'stock is not managed, qty to ship is the limit' => ['false', false],
            'stock is managed, qty available is the limit' => ['true', true],
        ];
    }
}

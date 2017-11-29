<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalog\Observer\ProcessSourceItemsObserver;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer as EventObserver;

class ProcessSourceItemsObserverTest extends TestCase
{
    /**
     * @var ProcessSourceItemsObserver
     */
    private $observer;
    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var Save
     */
    private $saveController;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var Event
     */
    private $event;
    /**
     * @var EventObserver
     */
    private $eventObserver;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->observer = $this->objectManager->get(ProcessSourceItemsObserver::class);
        $this->productFactory = $this->objectManager->get(ProductInterfaceFactory::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->sourceItemRepository = $this->objectManager->get(SourceItemRepositoryInterface::class);
        $this->request = $this->objectManager->get(RequestInterface::class);
        $this->eventObserver = $this->objectManager->get(EventObserver::class);
        $this->event = $this->objectManager->get(Event::class);
        $this->saveController = $this->getMockBuilder(Save::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->saveController->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
    }

    public function testIsQuantityAndStockStatusSavingToDefaultSource()
    {
        $sku = 'SKU-1';
        $quantity = '12';
        $isInStock = '1';
        $productFormData = [
            'product' => [
                'sku' => $sku,
                'quantity_and_stock_status' => [
                    'qty' => $quantity,
                    'is_in_stock' => $isInStock
                ]
            ]
        ];
        $this->request->setParams($productFormData);
        $product = $this->productFactory->create([
            'data' => [
                'sku' => $sku
            ]
        ]);
        $this->event->setData(
            ['controller' => $this->saveController, 'product' => $product]
        );
        $this->eventObserver->setEvent($this->event);

        $this->observer->execute($this->eventObserver);

        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(Product::SKU, $sku, 'eq')
            ->create();
        /** @var SourceItemSearchResultsInterface $result */
        $result = $this->sourceItemRepository->getList($searchCriteria);
        /** @var SourceItemInterface[] $sourceItems */
        $sourceItems = $result->getItems();
        $item = current($sourceItems);

        $this->assertEquals((float)$quantity, (float)$item->getQuantity());
        $this->assertEquals((int)$isInStock, $item->getStatus());
    }
}

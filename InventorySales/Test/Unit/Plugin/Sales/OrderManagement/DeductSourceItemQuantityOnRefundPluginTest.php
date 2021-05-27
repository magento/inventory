<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Plugin\Sales\OrderManagement;

use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\ReturnProcessor\DeductSourceItemQuantityOnRefund;
use Magento\InventorySales\Plugin\Sales\OrderManagement\DeductSourceItemQuantityOnRefundPlugin;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterfaceFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for reservation compensation to only happen for new credit memos
 */
class DeductSourceItemQuantityOnRefundPluginTest extends TestCase
{
    /**
     * @var DeductSourceItemQuantityOnRefundPlugin
     */
    var $deductSourcePlugin;

    /**
     * @var GetSkuFromOrderItemInterface|MockObject
     */
    var $getSkuFromOrderItem;

    /**
     * @var ItemsToRefundInterfaceFactory|MockObject
     */
    var $itemsToRefundFactory;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface|MockObject
     */
    var $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface|MockObject
     */
    var $getProductTypesBySkus;

    /**
     * @var DeductSourceItemQuantityOnRefund|MockObject
     */
    var $deductSourceItemQuantityOnRefund;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    var $orderRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getSkuFromOrderItem = $this->createMock(
            GetSkuFromOrderItemInterface::class
        );
        $this->itemsToRefundFactory = $this->createMock(
            ItemsToRefundInterfaceFactory::class
        );
        $this->isSourceItemManagementAllowedForProductType = $this->createMock(
            IsSourceItemManagementAllowedForProductTypeInterface::class
        );
        $this->getProductTypesBySkus = $this->createMock(
            GetProductTypesBySkusInterface::class
        );
        $this->deductSourceItemQuantityOnRefund = $this->createMock(
            DeductSourceItemQuantityOnRefund::class
        );
        $this->orderRepository = $this->createMock(
            OrderRepositoryInterface::class
        );

        $this->deductSourcePlugin = new DeductSourceItemQuantityOnRefundPlugin(
            $this->getSkuFromOrderItem,
            $this->itemsToRefundFactory,
            $this->isSourceItemManagementAllowedForProductType,
            $this->getProductTypesBySkus,
            $this->deductSourceItemQuantityOnRefund,
            $this->orderRepository
        );
    }

    /**
     * Test reservation compensation update for new credit memo
     */
    public function testUpdateReservationsOnCreditMemoCreate()
    {
        $creditMemoRepositoryInterface = $this->createMock(CreditmemoRepositoryInterface::class);
        $creditMemoInterface = $this->createMock(CreditmemoInterface::class);

        $creditMemoInterface->expects($this->once())->method('getEntityId')->willReturn(null);
        $creditMemoInterface->expects($this->once())->method('getItems')->willReturn([]);

        $this->deductSourcePlugin->beforeSave($creditMemoRepositoryInterface, $creditMemoInterface);
    }

    /**
     * Test reservation compensation update for credit edits
     */
    public function testUpdateReservationsOnCreditMemoUpdate()
    {
        $creditMemoRepositoryInterface = $this->createMock(CreditmemoRepositoryInterface::class);
        $creditMemoInterface = $this->createMock(CreditmemoInterface::class);

        $creditMemoInterface->expects($this->once())->method('getEntityId')->willReturn(1);
        $creditMemoInterface->expects($this->never())->method('getOrderId');

        $this->deductSourcePlugin->beforeSave($creditMemoRepositoryInterface, $creditMemoInterface);
    }
}

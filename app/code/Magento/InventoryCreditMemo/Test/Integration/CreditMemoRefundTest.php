<?php
/**
 * Copyright :copyright: Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCreditMemo\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryReservations\Model\GetReservationsQuantityInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CreditMemoRefundTest extends TestCase
{
    /**
     * @var CreditmemoService
     */
    private $creditMemoService;

    /**
     * @var InvoiceCollection
     */
    private $invoiceCollection;

    /**
     * @var CreditmemoFactory
     */
    private $creditMemoFactory;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantityInterface;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->creditMemoService = Bootstrap::getObjectManager()->get(CreditmemoService::class);
        $this->invoiceCollection = Bootstrap::getObjectManager()->get(InvoiceCollection::class);
        $this->creditMemoFactory = Bootstrap::getObjectManager()->get(CreditmemoFactory::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilderFactory = Bootstrap::getObjectManager()->get(SearchCriteriaBuilderFactory::class);
        $this->getReservationsQuantityInterface = Bootstrap::getObjectManager()->get(
            GetReservationsQuantityInterface::class
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testRefund(): void
    {
        $invoices = $this->invoiceCollection->getItems();
        $invoice = end($invoices);

        $creditMemo = $this->creditMemoFactory->createByInvoice($invoice);
        $this->creditMemoService->refund($creditMemo, true);

        //check source item qty.
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, 'simple')
            ->addFilter(SourceItemInterface::SOURCE_CODE, 'default')
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $sourceItem = reset($sourceItems);
        self::assertEquals(98, $sourceItem->getQuantity());

        //check reservation for creation.
        $reservationQty = $this->getReservationsQuantityInterface->execute('simple', 1);
        self::assertEquals(2, $reservationQty);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/invoice.php
     */
    public function testRefundIfBackToStock(): void
    {
        $invoices = $this->invoiceCollection->getItems();
        $invoice = end($invoices);

        $creditMemo = $this->creditMemoFactory->createByInvoice($invoice);
        foreach ($creditMemo->getItems() as $item) {
            $item->setBackToStock(1);
        }

        $this->creditMemoService->refund($creditMemo, true);

        //check source item qty.
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, 'simple')
            ->addFilter(SourceItemInterface::SOURCE_CODE, 'default')
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        $sourceItem = reset($sourceItems);
        self::assertEquals(100, $sourceItem->getQuantity());

        //check reservation for creation.
        $reservationQty = $this->getReservationsQuantityInterface->execute('simple', 1);
        self::assertEquals(2, $reservationQty);
    }

    /**
     * @codingStandardsIgnoreLine
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/invoice_for_order_with_qty_more_than_available.php
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The credit memo couldn't be saved.
     */
    public function testRefundIfQtyIsMoreThanAvailable(): void
    {
        $invoices = $this->invoiceCollection->getItems();
        $invoice = end($invoices);

        $creditMemo = $this->creditMemoFactory->createByInvoice($invoice);

        $this->creditMemoService->refund($creditMemo, true);
    }
}

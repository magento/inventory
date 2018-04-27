<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCreditMemo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryCreditMemo\Model\IfNotReturnToStockQtyCorrector;
use Magento\InventorySales\Model\StockByWebsiteIdResolver;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Store\Model\StoreManagerInterface;

class CorrectQtyIfNotReturnToStock implements ObserverInterface
{
    /**
     * @var StockByWebsiteIdResolver
     */
    private $stockByWebsiteIdResolver;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var IfNotReturnToStockQtyCorrector
     */
    private $ifNotReturnToStockQtyCorrector;

    /**
     * @param StockByWebsiteIdResolver $stockByWebsiteIdResolver
     * @param StoreManagerInterface $storeManager
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param IfNotReturnToStockQtyCorrector $ifNotReturnToStockQtyCorrector
     */
    public function __construct(
        StockByWebsiteIdResolver $stockByWebsiteIdResolver,
        StoreManagerInterface $storeManager,
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        IfNotReturnToStockQtyCorrector $ifNotReturnToStockQtyCorrector
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->storeManager = $storeManager;
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->ifNotReturnToStockQtyCorrector = $ifNotReturnToStockQtyCorrector;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        /* @var $creditMemo Creditmemo */
        $creditMemo = $observer->getEvent()->getCreditmemo();

        $stockId = $this->getStockId($creditMemo);

        $sources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);
        $source = reset($sources);

        $this->ifNotReturnToStockQtyCorrector->execute($creditMemo->getItems(), $source->getSourceCode(), $stockId);
    }

    /**
     * @param Creditmemo $creditMemo
     *
     * @return int
     */
    private function getStockId(Creditmemo $creditMemo): int
    {
        $storeId = $creditMemo->getStoreId();
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteIdResolver->get((int)$websiteId)->getStockId();

        return $stockId;
    }
}

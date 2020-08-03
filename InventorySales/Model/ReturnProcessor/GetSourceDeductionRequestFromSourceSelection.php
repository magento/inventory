<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventorySales\Model\ReturnProcessor;

use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductInterface;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductInterfaceFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Create source deduction request based on the source selection.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetSourceDeductionRequestFromSourceSelection
{
    /**
     * @var ItemToDeductInterfaceFactory
     */
    private $itemToDeductFactory;

    /**
     * @var SourceDeductionRequestInterfaceFactory
     */
    private $sourceDeductionRequestFactory;

    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelFactory;

    /**
     * @var SalesEventInterfaceFactory
     */
    private $salesEventFactory;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var SalesEventExtensionFactory;
     */
    private $salesEventExtensionFactory;

    /**
     * @param ItemToDeductInterfaceFactory $itemToDeductFactory
     * @param SourceDeductionRequestInterfaceFactory $sourceDeductionRequestFactory
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     * @param SalesEventInterfaceFactory $salesEventFactory
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param SalesEventExtensionFactory $salesEventExtensionFactory
     */
    public function __construct(
        ItemToDeductInterfaceFactory $itemToDeductFactory,
        SourceDeductionRequestInterfaceFactory $sourceDeductionRequestFactory,
        SalesChannelInterfaceFactory $salesChannelFactory,
        SalesEventInterfaceFactory $salesEventFactory,
        WebsiteRepositoryInterface $websiteRepository,
        SalesEventExtensionFactory $salesEventExtensionFactory
    ) {
        $this->itemToDeductFactory = $itemToDeductFactory;
        $this->sourceDeductionRequestFactory = $sourceDeductionRequestFactory;
        $this->salesChannelFactory = $salesChannelFactory;
        $this->salesEventFactory = $salesEventFactory;
        $this->websiteRepository = $websiteRepository;
        $this->salesEventExtensionFactory = $salesEventExtensionFactory;
    }

    /**
     * Returns source deduction request based on the source selection.
     *
     * @param OrderInterface $order
     * @param SourceSelectionResultInterface $sourceSelectionResult
     * @return array|SourceDeductionRequestInterface[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(
        OrderInterface $order,
        SourceSelectionResultInterface $sourceSelectionResult
    ): array {
        $websiteId = (int)$order->getStore()->getWebsiteId();

        $sourceDeductionRequests = [];
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
        $salesChannel = $this->salesChannelFactory->create([
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode
            ]
        ]);

        /** @var SalesEventExtensionInterface */
        $salesEventExtension = $this->salesEventExtensionFactory->create([
            'data' => ['objectIncrementId' => (string)$order->getIncrementId()]
        ]);

        $salesEvent = $this->salesEventFactory->create([
            'type' => SalesEventInterface::EVENT_CREDITMEMO_CREATED,
            'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string)$order->getEntityId()
        ]);
        $salesEvent->setExtensionAttributes($salesEventExtension);

        foreach ($this->getItemsPerSource($sourceSelectionResult->getSourceSelectionItems()) as $sourceCode => $items) {
            /** @var SourceDeductionRequestInterface[] $sourceDeductionRequests */
            $sourceDeductionRequests[] = $this->sourceDeductionRequestFactory->create([
                'sourceCode' => $sourceCode,
                'items' => $items,
                'salesChannel' => $salesChannel,
                'salesEvent' => $salesEvent
            ]);
        }

        return $sourceDeductionRequests;
    }

    /**
     * Returns items per source.
     *
     * @param array $sourceSelectionItems
     * @return ItemToDeductInterface[]
     */
    private function getItemsPerSource(array $sourceSelectionItems): array
    {
        $itemsPerSource = [];
        foreach ($sourceSelectionItems as $sourceSelectionItem) {
            if (bccomp((string)$sourceSelectionItem->getQtyToDeduct(), '0.000001', 6) === -1) {
                continue;
            }

            if (!isset($itemsPerSource[$sourceSelectionItem->getSourceCode()])) {
                $itemsPerSource[$sourceSelectionItem->getSourceCode()] = [];
            }
            $itemsPerSource[$sourceSelectionItem->getSourceCode()][] = $this->itemToDeductFactory->create([
                'sku' => $sourceSelectionItem->getSku(),
                'qty' => $sourceSelectionItem->getQtyToDeduct(),
            ]);
        }
        return $itemsPerSource;
    }
}

<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStock\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\InventoryExportStockApi\Api\Data\ExportStockSalableQtySearchResultInterface;
use Magento\InventoryExportStockApi\Api\ExportStockSalableQtyBySalesChannelInterface;
use Magento\InventoryExportStockApi\Api\ExportStockSalableQtyInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;

/**
 * @inheritDoc
 */
class ExportStockSalableQty implements ExportStockSalableQtyInterface
{
    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelInterfaceFactory;

    /**
     * @var ExportStockSalableQtyBySalesChannelInterface
     */
    private $exportStockSalableQtyBySalesChannel;

    /**
     * @param SalesChannelInterfaceFactory $salesChannelInterfaceFactory
     * @param ExportStockSalableQtyBySalesChannelInterface $exportStockSalableQtyBySalesChannel
     */
    public function __construct(
        SalesChannelInterfaceFactory $salesChannelInterfaceFactory,
        ExportStockSalableQtyBySalesChannelInterface $exportStockSalableQtyBySalesChannel
    ) {

        $this->salesChannelInterfaceFactory = $salesChannelInterfaceFactory;
        $this->exportStockSalableQtyBySalesChannel = $exportStockSalableQtyBySalesChannel;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        string $salesChannelType,
        string $salesChannelCode,
        SearchCriteriaInterface $searchCriteria
    ): ExportStockSalableQtySearchResultInterface {
        $salesChannel = $this->salesChannelInterfaceFactory->create(
            [
                'data' => [
                    SalesChannelInterface::TYPE => $salesChannelType,
                    SalesChannelInterface::CODE => $salesChannelCode
                ]
            ]
        );

        return $this->exportStockSalableQtyBySalesChannel->execute($salesChannel, $searchCriteria);
    }
}

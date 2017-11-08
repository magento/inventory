<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Inventory\Model\Stock\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventorySales\Model\ResourceModel\GetAssignedSalesChannelsDataForStockInterface;
use Magento\InventorySales\Model\ResourceModel\GetAssignedSalesChannelsForOtherStocksInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;



/**
 * Check that name is valid
 */
class SalesChannelsValidator implements StockValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var GetAssignedSourceIdsForStockInterface
     */
    private $assignedSalesChannelsForStock;

    /**
     * @var GetAssignedSourcesForStockInterface
     */
    private $assignedSalesChannelsForOtherStocks;

    /**
     * SalesChannelsValidator constructor.
     *
     * @param ValidationResultFactory                         $validationResultFactory
     * @param GetAssignedSalesChannelsDataForStockInterface   $assignedSalesChannelsForStock
     * @param GetAssignedSalesChannelsForOtherStocksInterface $assignedSalesChannelsForOtherStocks
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        GetAssignedSalesChannelsDataForStockInterface $assignedSalesChannelsForStock,
        GetAssignedSalesChannelsForOtherStocksInterface $assignedSalesChannelsForOtherStocks
    )
    {
        $this->validationResultFactory             = $validationResultFactory;
        $this->assignedSalesChannelsForStock       = $assignedSalesChannelsForStock;
        $this->assignedSalesChannelsForOtherStocks = $assignedSalesChannelsForOtherStocks;
    }

    /**
     * @inheritdoc
     */
    public function validate(StockInterface $stock): ValidationResult
    {
        $errors = [];
        if ($stock->getStockId()) {
            $channelsLinksData     = [];
            $assignedSalesChannels = $this->assignedSalesChannelsForStock->execute($stock->getStockId());
            $assignedChannelsCodes = array_flip(array_column($assignedSalesChannels, SalesChannelInterface::CODE));
            foreach ($stock->getExtensionAttributes()->getSalesChannels() as $salesChannel) {
                $channelsLinksData[$salesChannel->getCode()] = $salesChannel->getType();
            }

            $channelsForDelete = [];
            foreach ($assignedChannelsCodes as $code => $value) {
                if (!array_key_exists($code, $channelsLinksData)) {
                    $channelsForDelete[] = $code;
                }
            }

            foreach ($channelsForDelete as $code) {
                $assignedSourceIds = $this->assignedSalesChannelsForOtherStocks->execute($stock->getStockId(), $code);
                if (!count($assignedSourceIds)) {
                    $errors[] = __('Sales channel "%source" can not be unrelated not to one stock.', ['source' => $code]);
                }
            }
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

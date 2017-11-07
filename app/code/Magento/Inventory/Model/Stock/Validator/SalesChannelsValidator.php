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
use Magento\Framework\App\RequestInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Event\Magento;


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
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * SalesChannelsValidator constructor.
     *
     * @param ValidationResultFactory                         $validationResultFactory
     * @param GetAssignedSalesChannelsDataForStockInterface   $assignedSalesChannelsForStock
     * @param GetAssignedSalesChannelsForOtherStocksInterface $assignedSalesChannelsForOtherStocks
     * @param RequestInterface                                $request
     * @param StoreManagerInterface                           $storeManager
     */
    public function __construct(ValidationResultFactory $validationResultFactory,
                                GetAssignedSalesChannelsDataForStockInterface $assignedSalesChannelsForStock,
                                GetAssignedSalesChannelsForOtherStocksInterface $assignedSalesChannelsForOtherStocks,
                                RequestInterface $request,
                                StoreManagerInterface $storeManager)
    {
        $this->validationResultFactory             = $validationResultFactory;
        $this->assignedSalesChannelsForStock       = $assignedSalesChannelsForStock;
        $this->assignedSalesChannelsForOtherStocks = $assignedSalesChannelsForOtherStocks;
        $this->request                             = $request;
        $this->storeManager                  = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function validate(StockInterface $stock): ValidationResult
    {
        $errors = [];
        if (!$stock->isObjectNew()) {
            $assignedSalesChannels = $this->assignedSalesChannelsForStock->execute($stock->getStockId());
            $params                = $this->request->getParam('sales_channels', []);
            $channelsLinksData     = isset($params['websites']) && is_array($params['websites']) ? $params['websites'] : [];
            $assignedChannelsCodes = array_flip(array_column($assignedSalesChannels, SalesChannelInterface::CODE));

            $channelsForDelete = [];
            foreach ($assignedChannelsCodes as $code => $value) {
                if (!array_key_exists($code, array_flip($channelsLinksData))) {
                    $channelsForDelete[] = $code;
                }
            }

            foreach ($channelsForDelete as $code) {
                $assignedSourceIds = $this->assignedSalesChannelsForOtherStocks->execute($stock->getStockId(), $code);
                if (!count($assignedSourceIds)) {
                    $errors[] = __('Sales channel "%source" can not be unrelated not to one stock.',
                                   ['source' => $this->storeManager->getWebsite($code)->getName()]
                    );
                }

            }
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

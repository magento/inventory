<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Controller\Adminhtml\Stock;

use Magento\Backend\App\Action;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\Ui\Controller\UiActionInterface;

/**
 * Get stock item configuration for "Advanced Inventory" panel.
 */
class GetStockConfiguration extends Action implements UiActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var JsonValidator
     */
    private $jsonValidator;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonValidator $jsonValidator
     * @param Json $serializer
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        GetStockConfigurationInterface $getStockConfiguration,
        ScopeConfigInterface $scopeConfig,
        JsonValidator $jsonValidator,
        Json $serializer
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->getStockConfiguration = $getStockConfiguration;
        $this->scopeConfig = $scopeConfig;
        $this->jsonValidator = $jsonValidator;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $sku = (string)$this->getRequest()->getParam('sku', null);
        $stockId = (int)$this->getRequest()->getParam('stockId', null);

        if ($stockId === null) {
            return $resultJson->setData([]);
        }
        $stockItemConfiguration = null;
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();
        if ($sku) {
            $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        }

        $result = [
            'stock_id' => $stockId,
            'min_qty' => $this->getMinQty($globalConfiguration, $stockConfiguration, $stockItemConfiguration),
            'use_config_min_qty' => $this->getMinQtyUseConfig($stockItemConfiguration),
            'is_qty_decimal' => $this->isQtyDecimal($stockConfiguration, $stockItemConfiguration),
            'min_sale_qty' => $this->getMinSaleQty(
                $stockConfiguration,
                $stockItemConfiguration
            ),
            'use_config_min_sale_qty' => $this->getMinSaleQtyUseConfig($stockItemConfiguration),
            'max_sale_qty' => $this->getMaxSaleQty(
                $globalConfiguration,
                $stockConfiguration,
                $stockItemConfiguration
            ),
            'use_config_max_sale_qty' => $this->getMaxSaleQtyUseConfig($stockItemConfiguration),
            'manage_stock' => $this->getManageStock(
                $globalConfiguration,
                $stockConfiguration,
                $stockItemConfiguration
            ),
            'use_config_manage_stock' => $this->getManageStockUseConfig($stockItemConfiguration),
            'use_config_qty_increments' => $this->getQtyIncrementsUseConfig($stockItemConfiguration),
            'qty_increments' => $this->getQtyIncrements(
                $globalConfiguration,
                $stockConfiguration,
                $stockItemConfiguration
            ),
            'use_config_enable_qty_inc' => $this->getEnableQtyIncrementsUseConfig($stockItemConfiguration),
            'enable_qty_increments' => $this->getEnableQtyIncrements(
                $globalConfiguration,
                $stockConfiguration,
                $stockItemConfiguration
            ),
            'is_decimal_divided' => $this->isDecimalDevided($stockConfiguration, $stockItemConfiguration),
            'min_qty_allowed_in_shopping_cart' => $this->getMinSaleQtyDefaultConfig(),
        ];

        return $resultJson->setData($result);
    }

    /**
     * @inheritdoc
     */
    public function executeAjaxRequest()
    {
        $this->execute();
    }

    /**
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return int
     */
    private function getManageStock(
        StockItemConfigurationInterface $globalStockConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration = null
    ): int {
        $itemValue = null;
        if ($stockItemConfiguration !== null) {
            $itemValue = $stockItemConfiguration->isManageStock();
        }
        $globalValue = $globalStockConfiguration->isManageStock();
        $stockValue = $stockConfiguration->isManageStock();

        $defaultValue = $stockValue !== null ? $stockValue : $globalValue;

        return $itemValue !== null ? (int)$itemValue : (int)$defaultValue;
    }

    /**
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return float
     */
    private function getMinQty(
        StockItemConfigurationInterface $globalStockConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration = null
    ): float {
        $itemValue = null;
        if ($stockItemConfiguration !== null) {
            $itemValue = $stockItemConfiguration->getMinQty();
        }
        $globalValue = $globalStockConfiguration->getMinQty();
        $stockValue = $stockConfiguration->getMinQty();

        $defaultValue = $stockValue !== null ? $stockValue : $globalValue;

        return $itemValue !== null ? $itemValue : $defaultValue;
    }

    /**
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return float
     */
    private function getMaxSaleQty(
        StockItemConfigurationInterface $globalStockConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration = null
    ): float {
        $itemValue = null;
        if ($stockItemConfiguration !== null) {
            $itemValue = $stockItemConfiguration->getMaxSaleQty();
        }
        $globalValue = $globalStockConfiguration->getMaxSaleQty();
        $stockValue = $stockConfiguration->getMaxSaleQty();

        $defaultValue = $stockValue !== null ? $stockValue : $globalValue;

        return $itemValue !== null ? $itemValue : $defaultValue;
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return float|null
     */
    private function getMinSaleQty(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration = null
    ): ?float {
        $stockItemValue = $stockItemConfiguration !== null || $stockItemConfiguration->getMinSaleQty() !== null
            ? $stockItemConfiguration->getMinSaleQty()
            : null;
        $stockValue = $stockConfiguration->getMinSaleQty();

        return $stockItemValue !== null ? $stockItemValue : $stockValue;
    }

    /**
     * @return array
     */
    private function getMinSaleQtyDefaultConfig(): array
    {
        if (empty($this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MIN_SALE_QTY))) {
            return [];
        }
        $minSaleQtyData = $this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MIN_SALE_QTY);
        if (is_string($minSaleQtyData) && $this->jsonValidator->isValid($minSaleQtyData)) {
            // Set data source for dynamicRows minimum qty allowed in shopping cart
            $unserializedMinSaleQty = $this->serializer->unserialize($minSaleQtyData);
            if (is_array($unserializedMinSaleQty)) {
                $minSaleQtyData = array_map(
                    function ($group, $qty) {
                        return [
                            StockItemInterface::CUSTOMER_GROUP_ID => $group,
                            StockItemInterface::MIN_SALE_QTY => $qty,
                        ];
                    },
                    array_keys($unserializedMinSaleQty),
                    array_values($unserializedMinSaleQty)
                );
            } else {
                $minSaleQtyData = [$unserializedMinSaleQty];
            }
        }

        return $minSaleQtyData;
    }

    /**
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return string
     */
    private function getMinQtyUseConfig(StockItemConfigurationInterface $stockItemConfiguration = null)
    {
        return $stockItemConfiguration === null || $stockItemConfiguration->getMinQty() === null ? '1' : '0';
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return int
     */
    private function isQtyDecimal(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration = null
    ): int {
        $itemConfiguration = $stockItemConfiguration === null || $stockItemConfiguration->isQtyDecimal() === null
            ? null
            : $stockItemConfiguration->isQtyDecimal();

        return $itemConfiguration !== null ? (int)$itemConfiguration : (int)$stockConfiguration->isQtyDecimal();
    }

    /**
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return float
     */
    private function getQtyIncrements(
        StockItemConfigurationInterface $globalStockConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration = null
    ): float {
        $itemValue = null;
        if ($stockItemConfiguration !== null) {
            $itemValue = $stockItemConfiguration->getQtyIncrements();
        }
        $globalValue = $globalStockConfiguration->getQtyIncrements();
        $stockValue = $stockConfiguration->getQtyIncrements();

        $defaultValue = $stockValue !== null ? $stockValue : $globalValue;

        return $itemValue !== null ? $itemValue : $defaultValue;
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return int
     */
    private function isDecimalDevided(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration
    ): int {
        $itemConfiguration = $stockItemConfiguration === null || $stockItemConfiguration->isDecimalDivided() === null
            ? null
            : $stockItemConfiguration->isDecimalDivided();

        return $itemConfiguration !== null ? (int)$itemConfiguration : (int)$stockConfiguration->isDecimalDivided();
    }

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return string
     */
    private function getQtyIncrementsUseConfig(StockItemConfigurationInterface $stockItemConfiguration = null)
    {
        return $stockItemConfiguration === null || $stockItemConfiguration->getQtyIncrements() === null
            ? '1'
            : '0';
    }

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return string
     */
    private function getEnableQtyIncrementsUseConfig(StockItemConfigurationInterface $stockItemConfiguration)
    {
        return $stockItemConfiguration === null || $stockItemConfiguration->isEnableQtyIncrements() === null
            ? '1'
            : '0';
    }

    /**
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return string
     */
    private function getManageStockUseConfig(StockItemConfigurationInterface $stockItemConfiguration = null)
    {
        return $stockItemConfiguration === null || $stockItemConfiguration->isManageStock() === null
            ? '1'
            : '0';
    }

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return string
     */
    private function getMaxSaleQtyUseConfig(StockItemConfigurationInterface $stockItemConfiguration)
    {
        return $stockItemConfiguration === null || $stockItemConfiguration->getMaxSaleQty() === null
            ? '1'
            : '0';
    }

    /**
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return int
     */
    private function getEnableQtyIncrements(
        StockItemConfigurationInterface $globalStockConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration = null
    ): int {
        $itemValue = null;
        if ($stockItemConfiguration !== null) {
            $itemValue = $stockItemConfiguration->isEnableQtyIncrements();
        }
        $globalValue = $globalStockConfiguration->isEnableQtyIncrements();
        $stockValue = $stockConfiguration->isEnableQtyIncrements();

        $defaultValue = $stockValue !== null ? $stockValue : $globalValue;

        return $itemValue !== null ? (int)$itemValue : (int)$defaultValue;
    }

    /**
     * @param StockItemConfigurationInterface|null $stockItemConfiguration
     * @return string
     */
    private function getMinSaleQtyUseConfig(StockItemConfigurationInterface $stockItemConfiguration = null)
    {
        return $stockItemConfiguration !== null || $stockItemConfiguration->getMinSaleQty() !== null
            ? '0'
            : '1';
    }
}
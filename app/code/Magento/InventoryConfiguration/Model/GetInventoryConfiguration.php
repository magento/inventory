<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryConfigurationApi\Api\GetInventoryConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfiguration\Model\ResourceModel\GetSourceCodesBySkuAndStockId;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfiguration\Model\GetSystemMinSaleQty;

class GetInventoryConfiguration implements GetInventoryConfigurationInterface
{
    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var GetSourceCodesBySkuAndStockId
     */
    private $getSourceCodesBySkuAndStockId;

    /**
     * @var GetSystemMinSaleQty
     */
    private $getSystemMinSaleQty;

    /**
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param GetSourceCodesBySkuAndStockId $getSourceCodesBySkuAndStockId
     * @param \Magento\InventoryConfiguration\Model\GetSystemMinSaleQty $getSystemMinSaleQty
     */
    public function __construct(
        GetSourceConfigurationInterface $getSourceConfiguration,
        GetStockConfigurationInterface $getStockConfiguration,
        GetSourceCodesBySkuAndStockId $getSourceCodesBySkuAndStockId,
        GetSystemMinSaleQty $getSystemMinSaleQty
    ) {
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->getStockConfiguration = $getStockConfiguration;
        $this->getSourceCodesBySkuAndStockId = $getSourceCodesBySkuAndStockId;
        $this->getSystemMinSaleQty = $getSystemMinSaleQty;
    }

    /**
     * @inheritdoc
     */
    public function isQtyDecimal(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->isQtyDecimal();
        if (isset($stockItemConfiguration)) {
            return (bool)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->isQtyDecimal();
            if (isset($stockConfiguration)) {
                return (bool)$stockConfiguration;
            } else {
                return false;
            }
        }
    }

    /**
     * @return bool
     */
    public function isShowDefaultNotificationMessage(): bool
    {
        // TODO: Implement isShowDefaultNotificationMessage() method.
    }

    /**
     * @inheritdoc
     */
    public function getMinQty(string $sku, int $stockId): float
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->getMinQty();
        if (isset($stockItemConfiguration)) {
            return (float)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->getMinQty();
            if (isset($stockConfiguration)) {
                return (float)$stockConfiguration;
            } else {
                return (float)$this->getStockConfiguration->forGlobal()->getMinQty();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getMinSaleQty(string $sku, int $stockId): float
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->getMaxSaleQty();
        if (isset($stockItemConfiguration)) {
            return (float)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->getMaxSaleQty();
            if (isset($stockConfiguration)) {
                return (float)$stockConfiguration;
            } else {
                return $this->getSystemMinSaleQty->execute();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getMaxSaleQty(string $sku, int $stockId): float
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->getMaxSaleQty();
        if (isset($stockItemConfiguration)) {
            return (float)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->getMaxSaleQty();
            if (isset($stockConfiguration)) {
                return (float)$stockConfiguration;
            } else {
                return (float)$this->getStockConfiguration->forGlobal()->getMaxSaleQty();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getBackorders(string $sku, int $stockId): int
    {
        $backOrders = [];
        $sourceCodes = $this->getSourceCodesBySkuAndStockId->execute($sku, $stockId);
        foreach ($sourceCodes as $sourceCode) {
            $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem($sku, $sourceCode)->getBackorders();
            if (isset($sourceItemConfiguration)) {
                $backOrders[] = (int)$sourceItemConfiguration;
            } else {
                $sourceConfiguration = $this->getSourceConfiguration->forSource($sourceCode)->getBackorders();
                if (isset($sourceConfiguration)) {
                    $backOrders[] = (int)$sourceConfiguration;
                } else {
                    $backOrders[] = (int)$this->getSourceConfiguration->forGlobal()->getBackorders();
                }
            }
        }

        return max($backOrders);
    }

    /**
     * @inheritdoc
     */
    public function getQtyIncrements(string $sku, int $stockId): float
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->getQtyIncrements();
        if (isset($stockItemConfiguration)) {
            return (float)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->getQtyIncrements();
            if (isset($stockConfiguration)) {
                return (float)$stockConfiguration;
            } else {
                return (float)$this->getStockConfiguration->forGlobal()->getQtyIncrements();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function isEnableQtyIncrements(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->isEnableQtyIncrements();
        if (isset($stockItemConfiguration)) {
            return (bool)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->isEnableQtyIncrements();
            if (isset($stockConfiguration)) {
                return (bool)$stockConfiguration;
            } else {
                return (bool)$this->getStockConfiguration->forGlobal()->isEnableQtyIncrements();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function isManageStock(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->isManageStock();
        if (isset($stockItemConfiguration)) {
            return (bool)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->isManageStock();
            if (isset($stockConfiguration)) {
                return (bool)$stockConfiguration;
            } else {
                return (bool)$this->getStockConfiguration->forGlobal()->isManageStock();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getLowStockDate(string $sku, int $stockId): string
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->getLowStockDate();
        if (isset($stockItemConfiguration)) {
            return (string)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->getLowStockDate();
            if (isset($stockConfiguration)) {
                return (string)$stockConfiguration;
            } else {
                return (string)$this->getStockConfiguration->forGlobal()->getLowStockDate();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function isDecimalDivided(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->isDecimalDivided();
        if (isset($stockItemConfiguration)) {
            return (bool)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->isDecimalDivided();
            if (isset($stockConfiguration)) {
                return (bool)$stockConfiguration;
            } else {
                return (bool)$this->getStockConfiguration->forGlobal()->isDecimalDivided();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getStockThresholdQty(string $sku, int $stockId): float
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId)->getStockThresholdQty();
        if (isset($stockItemConfiguration)) {
            return (float)$stockItemConfiguration;
        } else {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId)->getStockThresholdQty();
            if (isset($stockConfiguration)) {
                return (float)$stockConfiguration;
            } else {
                return (float)$this->getStockConfiguration->forGlobal()->getStockThresholdQty();
            }
        }
    }
}

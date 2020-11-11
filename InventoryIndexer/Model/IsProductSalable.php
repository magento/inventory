<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Psr\Log\LoggerInterface;

/**
 * Lightweight implementation for Storefront application.
 */
class IsProductSalable implements IsProductSalableInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param GetStockItemDataInterface $getStockItemData
     * @param ScopeConfigInterface $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetStockItemDataInterface $getStockItemData,
        ScopeConfigInterface $config,
        LoggerInterface $logger
    ) {
        $this->getStockItemData = $getStockItemData;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        try {
            $showOutOfStock = (int)$this->config->getValue('cataloginventory/options/show_out_of_stock');
            $stockItem = $this->getStockItemData->execute($sku, $stockId);
            $isSalable = $showOutOfStock ? true : (bool)($stockItem[GetStockItemDataInterface::IS_SALABLE] ?? false);
        } catch (LocalizedException $exception) {
            $this->logger->warning(
                sprintf(
                    'Unable to fetch stock #%s data for SKU %s. Reason: %s',
                    $stockId,
                    $sku,
                    $exception->getMessage()
                )
            );
            $isSalable = false;
        }

        return $isSalable;
    }
}

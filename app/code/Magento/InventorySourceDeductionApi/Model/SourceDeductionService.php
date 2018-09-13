<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySourceDeductionApi\Model;

use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @inheritdoc
 */
class SourceDeductionService implements SourceDeductionServiceInterface
{
    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var GetSourceItemBySourceCodeAndSku
     */
    private $getSourceItemBySourceCodeAndSku;

    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku
     * @param GetStockConfigurationInterface $getStockItemConfiguration
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        SourceItemsSaveInterface $sourceItemsSave,
        GetSourceItemBySourceCodeAndSku $getSourceItemBySourceCodeAndSku,
        GetStockConfigurationInterface $getStockItemConfiguration,
        StockResolverInterface $stockResolver
    ) {
        $this->sourceItemsSave = $sourceItemsSave;
        $this->getSourceItemBySourceCodeAndSku = $getSourceItemBySourceCodeAndSku;
        $this->getStockConfiguration = $getStockItemConfiguration;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(SourceDeductionRequestInterface $sourceDeductionRequest): void
    {
        $sourceItems = [];
        $sourceCode = $sourceDeductionRequest->getSourceCode();
        $salesChannel = $sourceDeductionRequest->getSalesChannel();

        $stockId = (int)$this->stockResolver->get(
            $salesChannel->getType(),
            $salesChannel->getCode()
        )->getStockId();
        foreach ($sourceDeductionRequest->getItems() as $item) {
            $itemSku = $item->getSku();
            $qty = $item->getQty();
     
            $isManageStock = $this->isManageStock($itemSku, $stockId);
            if (!$isManageStock) {
                //We don't need to Manage Stock
                continue;
            }

            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($sourceCode, $itemSku);
            if (($sourceItem->getQuantity() - $qty) >= 0) {
                $sourceItem->setQuantity($sourceItem->getQuantity() - $qty);
                $sourceItems[] = $sourceItem;
            } else {
                throw new LocalizedException(
                    __('Not all of your products are available in the requested quantity.')
                );
            }
        }

        if (!empty($sourceItems)) {
            $this->sourceItemsSave->execute($sourceItems);
        }
    }

    /**
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    private function isManageStock(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();

        $defaultValue = $stockConfiguration->isManageStock() !== null
            ? $stockConfiguration->isManageStock()
            : $globalConfiguration->isManageStock();

        return $stockItemConfiguration->isManageStock() !== null
            ? $stockItemConfiguration->isManageStock()
            : $defaultValue;
    }
}

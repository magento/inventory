<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Inventory;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Update parent products stock status after decrementing quantity of children stock
 */
class UpdateCompositeProductStockStatusOnDecrementSourceItemQty
{
    /**
     * @var GetProductIdsBySkusInterface
     */
    private GetProductIdsBySkusInterface $getProductIdsBySkus;

    /**
     * @var IsSingleSourceModeInterface
     */
    private IsSingleSourceModeInterface $isSingleSourceMode;

    /**
     * @var CompositeProductStockStatusProcessorInterface
     */
    private CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        IsSingleSourceModeInterface $isSingleSourceMode,
        CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->compositeProductStockStatusProcessor = $compositeProductStockStatusProcessor;
    }

    /**
     * Update parent products stock status after decrementing quantity of children stock
     *
     * @param DecrementSourceItemQty $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItemDecrementData
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(DecrementSourceItemQty $subject, $result, array $sourceItemDecrementData): void
    {
        if ($this->isSingleSourceMode->execute()) {
            $productIds = [];
            $sourceItems = array_column($sourceItemDecrementData, 'source_item');
            foreach ($sourceItems as $sourceItem) {
                $sku = $sourceItem->getSku();
                try {
                    $productIds[] = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
                } catch (NoSuchEntityException $e) {
                    continue;
                }
            }
            if ($productIds) {
                $this->compositeProductStockStatusProcessor->execute($productIds);
            }
        }
    }
}

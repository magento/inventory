<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Inventory;

use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Update parent products stock status after decrementing quantity of children stock
 */
class UpdateCompositeProductStockStatusOnDecrementSourceItemQty
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private IsSingleSourceModeInterface $isSingleSourceMode;

    /**
     * @var CompositeProductStockStatusProcessorInterface
     */
    private CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor
    ) {
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
            $sourceItems = array_column($sourceItemDecrementData, 'source_item');
            $skus = [];
            foreach ($sourceItems as $sourceItem) {
                $skus[] = $sourceItem->getSku();
            }
            if ($skus) {
                $this->compositeProductStockStatusProcessor->execute($skus);
            }
        }
    }
}

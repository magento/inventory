<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryApi;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Update parent products stock status on children products source items update
 */
class UpdateCompositeProductStockStatusOnSourceItemsSave
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
     * Update parent products stock status on children products source items update
     *
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(SourceItemsSaveInterface $subject, $result, array $sourceItems): void
    {
        if ($this->isSingleSourceMode->execute()) {
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

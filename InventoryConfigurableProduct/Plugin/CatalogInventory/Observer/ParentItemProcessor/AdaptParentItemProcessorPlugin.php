<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Observer\ParentItemProcessor;

use Closure;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Observer\ParentItemProcessorInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateLegacyStockStatus;

/**
 * Process configurable product stock status.
 */
class AdaptParentItemProcessorPlugin
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var UpdateLegacyStockStatus
     */
    private $updateLegacyStockStatus;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param Configurable $configurableType
     * @param UpdateLegacyStockStatus $updateLegacyStockStatus
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        Configurable $configurableType,
        UpdateLegacyStockStatus $updateLegacyStockStatus,
        StockRegistryInterface $stockRegistry
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->configurableType = $configurableType;
        $this->updateLegacyStockStatus = $updateLegacyStockStatus;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Process configurable product stock status considering source mode.
     *
     * @param ParentItemProcessorInterface $subject
     * @param Closure $proceed
     * @param ProductInterface $product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundProcess(
        ParentItemProcessorInterface $subject,
        Closure $proceed,
        ProductInterface $product
    ): void {
        if ($this->isSingleSourceMode->execute()) {
            $proceed($product);
        } else {
            $parentIds = $this->configurableType->getParentIdsByChild($product->getId());
            $skus = $this->getSkusByProductIds->execute($parentIds);

            $dataForUpdate = [];
            foreach ($parentIds as $parentId) {
                $parentStockItem = $this->stockRegistry->getStockItem($parentId);
                if ($parentStockItem->getIsInStock()) {
                    $dataForUpdate[$skus[$parentId]] = true;
                }
            }
            $this->updateLegacyStockStatus->execute($dataForUpdate);
        }
    }
}

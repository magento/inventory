<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Observer\ParentItemProcessor;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Observer\ParentItemProcessorInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

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
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(IsSingleSourceModeInterface $isSingleSourceMode)
    {
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Process configurable product stock status considering source mode.
     *
     * @param ParentItemProcessorInterface $subject
     * @param \Closure $proceed
     * @param ProductInterface $product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundProcess(
        ParentItemProcessorInterface $subject,
        \Closure $proceed,
        ProductInterface $product
    ): void {
        if ($this->isSingleSourceMode->execute()) {
            $proceed($product);
        }
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Observer\ParentItemProcessor;

use Closure;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Observer\ParentItemProcessorInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Process composite product stock status.
 */
class SkipParentItemProcessorOnMultipleSourceMode
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Process composite product stock status considering source mode.
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
        }
    }
}

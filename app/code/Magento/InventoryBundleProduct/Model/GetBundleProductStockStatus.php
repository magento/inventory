<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Bundle\Model\OptionRepository;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Get bundle product stock status service.
 */
class GetBundleProductStockStatus
{
    /**
     * @var OptionRepository
     */
    private $optionRepository;

    /**
     * @var GetProductSelection
     */
    private $getProductSelection;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * GetBundleProductStockStatus constructor
     *
     * @param OptionRepository $optionRepository
     * @param GetProductSelection $getProductSelection
     * @param IsProductSalableInterface $isProductSalable
     */
    public function __construct(
        OptionRepository $optionRepository,
        GetProductSelection $getProductSelection,
        IsProductSalableInterface $isProductSalable
    ) {
        $this->optionRepository = $optionRepository;
        $this->getProductSelection = $getProductSelection;
        $this->isProductSalable = $isProductSalable;
    }

    /**
     * Provides bundle product stock status.
     *
     * @param ProductInterface $product
     * @param OptionInterface[] $bundleOptions
     * @param int $stockId
     *
     * @return bool
     */
    public function execute(ProductInterface $product, array $bundleOptions, int $stockId): bool
    {
        $isSalable = false;
        foreach ($bundleOptions as $option) {
            $hasSalable = false;
            $selectionsCollection = $this->getProductSelection->execute($product, $option);
            foreach ($selectionsCollection as $selection) {
                if ($this->isProductSalable->execute((string)$selection->getSku(), $stockId)) {
                    $hasSalable = true;
                    break;
                }
            }

            if ($hasSalable) {
                $isSalable = true;
            }

            if (!$hasSalable && $option->getRequired()) {
                $isSalable = false;
                break;
            }
        }

        return $isSalable;
    }
}

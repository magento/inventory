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
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

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
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param OptionRepository $optionRepository
     * @param GetProductSelection $getProductSelection
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        OptionRepository $optionRepository,
        GetProductSelection $getProductSelection,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->optionRepository = $optionRepository;
        $this->getProductSelection = $getProductSelection;
        $this->areProductsSalable = $areProductsSalable;
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
            $bundleSelections = $this->getProductSelection->execute($product, $option);
            $skus = [];
            foreach ($bundleSelections as $selection) {
                $skus[] = $selection->getSku();
            }
            $results = $this->areProductsSalable->execute($skus, $stockId);
            foreach ($results as $result) {
                if ($result->isSalable()) {
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

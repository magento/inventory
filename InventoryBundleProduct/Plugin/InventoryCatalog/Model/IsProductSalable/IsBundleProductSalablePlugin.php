<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\InventoryCatalog\Model\IsProductSalable;

use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Model\ResourceModel\Option\Collection;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\InventoryBundleProduct\Model\GetProductSelections;
use Magento\InventoryCatalog\Model\IsProductSalable;

/**
 * Verify bundle product salable status.
 */
class IsBundleProductSalablePlugin
{
    /**
     * @var Type
     */
    private $type;

    /**
     * @var GetProductSelections
     */
    private $getProductSelections;

    /**
     * @param Type $type
     * @param GetProductSelections $getProductSelections
     */
    public function __construct(Type $type, GetProductSelections $getProductSelections)
    {
        $this->type = $type;
        $this->getProductSelections = $getProductSelections;
    }

    /**
     * Get bundle product status.
     *
     * @param IsProductSalable $subject
     * @param \Closure $proceed
     * @param ProductInterface $product
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(IsProductSalable $subject, \Closure $proceed, ProductInterface $product): bool
    {
        if ($product->getTypeId() !== Type::TYPE_CODE) {
            return $proceed($product);
        }
        if ($product->hasData('all_items_salable')) {
            return $product->getData('all_items_salable');
        }

        $isSalable = $proceed($product);

        if (!$isSalable) {
            return false;
        }
        $bundleOptions = $this->type->getOptionsCollection($product);
        $isSalable = $this->isSalable($bundleOptions, $product);
        $product->setData('all_items_salable', $isSalable);

        return $isSalable;
    }

    /**
     * Verify bundle product has salable option.
     *
     * @param Collection $bundleOptions
     * @param ProductInterface $product
     * @return bool
     * @throws \Exception
     */
    private function isSalable(Collection $bundleOptions, ProductInterface $product): bool
    {
        $isSalable = false;
        foreach ($bundleOptions as $option) {
            $selections = $this->getProductSelections->execute($product, $option);
            $hasSalable = false;
            foreach ($selections as $selection) {
                if ((int)$selection->getStatus() === Status::STATUS_ENABLED) {
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

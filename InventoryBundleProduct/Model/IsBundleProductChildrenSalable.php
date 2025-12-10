<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\Bundle\Model\Option;
use Magento\Bundle\Model\ResourceModel\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Bundle\Model\ResourceModel\Selection\Collection\FilterApplier as SelectionCollectionFilterApplier;
use Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory as SelectionCollectionFactory;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;

class IsBundleProductChildrenSalable
{
    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param OptionCollectionFactory $optionCollectionFactory
     * @param SelectionCollectionFactory $selectionCollectionFactory
     * @param SelectionCollectionFilterApplier $selectionCollectionFilterApplier
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     */
    public function __construct(
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly OptionCollectionFactory $optionCollectionFactory,
        private readonly SelectionCollectionFactory $selectionCollectionFactory,
        private readonly SelectionCollectionFilterApplier $selectionCollectionFilterApplier,
        private readonly IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
    ) {
    }

    /**
     * Get bundle product salable status based on selections salable status
     *
     * Returns TRUE if:
     *
     *  - All options are optional: at least one selection is salable
     *  - Some options are required: at least one selection is salable in each required option
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function execute(string $sku, int $stockId): bool
    {
        $isSalable = false;

        $options = $this->getOptions($sku);
        foreach ($options as $option) {
            $isOptionSalable = $this->isOptionSalable($option, $stockId);
            if ($isOptionSalable) {
                $isSalable = true;
            } elseif ($option->getRequired()) {
                $isSalable = false;
                break;
            }
        }

        return $isSalable;
    }

    /**
     * Get bundle product options with selections.
     *
     * @param string $sku
     * @return Option[]
     */
    private function getOptions(string $sku): array
    {
        $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];

        $optionCollection = $this->optionCollectionFactory->create();
        $optionCollection->setProductIdFilter($productId);
        /** @var Option[] $options */
        $options = $optionCollection->getItems();
        $optionIds = array_keys($options);
        if (!$optionIds) {
            return [];
        }

        $selectionCollection = $this->selectionCollectionFactory->create();
        $this->selectionCollectionFilterApplier->apply(
            $selectionCollection,
            'parent_product_id',
            array_values($options)[0]->getParentId()
        );
        $selectionCollection->setOptionIdsFilter($optionIds);
        /** @var \Magento\Catalog\Model\Product[]|\Magento\Bundle\Model\Selection[] $selections */
        $selections = $selectionCollection->getItems();
        foreach ($selections as $selection) {
            $options[$selection->getOptionId()]->addSelection($selection);
        }

        return $options;
    }

    /**
     * Check if at least one selection in the option is salable.
     *
     * @param Option $option
     * @param int $stockId
     * @return bool
     */
    private function isOptionSalable(Option $option, int $stockId): bool
    {
        $isSalable = false;

        /** @var \Magento\Catalog\Model\Product|\Magento\Bundle\Model\Selection $selection */
        foreach ((array) $option->getSelections() as $selection) {
            $qty = $selection->getSelectionCanChangeQty() ? 1 : (float) $selection->getSelectionQty();
            $isSalable = $this->isProductSalableForRequestedQty->execute($selection->getSku(), $stockId, $qty)
                ->isSalable();
            if ($isSalable) {
                break;
            }
        }

        return $isSalable;
    }
}

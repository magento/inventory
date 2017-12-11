<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Source\Item\Importer;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Inventory\Model\SourceItemFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;

class DefaultSourceProcessor
{
    /**
     * Source Item Interface Factory
     *
     * @var SourceItemFactory $sourceItemFactory
     */
    private $sourceItemFactory;

    /**
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSourceProvider
     */
    private $defaultSourceProvider;

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        SourceItemFactory $sourceItemFactory,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * Return Source Item Interface for Default Source Item from given data
     *
     * @param array $data
     * @return SourceItemInterface
     */
    public function execute(array $data)
    {
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSku($data[Product::COL_SKU]);
        $sourceItem->setSourceId($this->defaultSourceProvider->getId());
        $sourceItem->setQuantity($this->getQty($data['qty']));
        $sourceItem->setStatus($this->getIsInStockValue($data));
        return $sourceItem;
    }

    /**
     * Return qty from value passed
     *
     * @param $qty
     * @return int|string
     */
    private function getQty($qty)
    {
        // If not numeric $qty must be "default={{value}}"
        if (!is_numeric($qty)) {
            // Explode at the "="
            $parts = explode('=', $qty);
            // Return the number after the "="
            return (int)$parts[1];
        }
        return $qty;
    }

    /**
     * Work out is_in_stock value it can be in different formats, with an '=' between source id and value
     *
     * @param $data
     * @return int
     */
    private function getIsInStockValue($data)
    {
        $inStock = 0;
        if (isset($data['is_in_stock'])) {
            $stockValue = $data['is_in_stock'];
            if (!is_numeric($stockValue) && strpos($stockValue, '=')) {
                $parts = explode('=', $stockValue);
                $inStock = $parts[1];
            } else {
                $inStock = $stockValue;
            }
        }
        return $inStock;
    }
}

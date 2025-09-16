<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStock\Model;

use Magento\InventoryExportStockApi\Api\Data\ProductStockIndexDataInterface;

/**
 * Maps product stock index data from an input array to a `ProductStockIndexDataInterface` object using a factory.
 */
class ProductStockIndexDataMapper
{
    /**
     * @var ProductStockIndexDataFactory
     */
    private $productStockIndexDataFactory;

    /**
     * @param ProductStockIndexDataFactory $productStockIndexDataFactory
     */
    public function __construct(
        ProductStockIndexDataFactory $productStockIndexDataFactory
    ) {
        $this->productStockIndexDataFactory = $productStockIndexDataFactory;
    }

    /**
     * Creates ProductStockIndexData object and set values inside of it
     *
     * @param array $item
     * @return ProductStockIndexDataInterface
     */
    public function execute(array $item): ProductStockIndexDataInterface
    {
        /** @var ProductStockIndexDataInterface $productStockDataObject */
        $productStockDataObject = $this->productStockIndexDataFactory->create();
        $productStockDataObject->setSku($item[ProductStockIndexDataInterface::SKU]);
        $productStockDataObject->setIsSalable((bool)$item[ProductStockIndexDataInterface::IS_SALABLE]);
        $productStockDataObject->setQty((float)$item[ProductStockIndexDataInterface::QTY]);

        return $productStockDataObject;
    }
}

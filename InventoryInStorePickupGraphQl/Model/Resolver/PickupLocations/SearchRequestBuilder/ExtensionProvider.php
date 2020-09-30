<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequestBuilder;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtension;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionFactory;

/**
 * Get Extension Attributes from input arguments.
 */
class ExtensionProvider
{
    private const PRODUCTS_INFO = 'productsInfo';
    private const SKU = 'sku';

    /**
     * @var SearchRequestExtensionFactory
     */
    private $searchRequestExtensionFactory;

    /**
     * @var ProductInfoInterfaceFactory
     */
    private $productInfoInterfaceFactory;

    /**
     * @param SearchRequestExtensionFactory $searchRequestExtensionFactory
     * @param ProductInfoInterfaceFactory $productInfoInterfaceFactory
     */
    public function __construct(
        SearchRequestExtensionFactory $searchRequestExtensionFactory,
        ProductInfoInterfaceFactory $productInfoInterfaceFactory
    ) {
        $this->searchRequestExtensionFactory = $searchRequestExtensionFactory;
        $this->productInfoInterfaceFactory = $productInfoInterfaceFactory;
    }

    /**
     * Get Extension Attributes
     *
     * @param array $argument
     *
     * @return SearchRequestExtension
     */
    public function getExtensionAttributes(array $argument): SearchRequestExtension
    {
        $extension = $this->searchRequestExtensionFactory->create();

        if (isset($argument[self::PRODUCTS_INFO])) {
            $extension->setProductsInfo($this->getProductsInfo($argument));
        }

        return $extension;
    }

    /**
     * Get array of products information.
     *
     * @param array $argument
     *
     * @return array
     */
    private function getProductsInfo(array $argument): array
    {
        $productsInfo = [];

        foreach ($argument[self::PRODUCTS_INFO] as $productInfoData) {
            $productsInfo[] = $this->productInfoInterfaceFactory->create([self::SKU => $productInfoData[self::SKU]]);
        }

        return $productsInfo;
    }
}

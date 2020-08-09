<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;

/**
 * Resolve Products Information.
 */
class ProductsInfo implements ResolverInterface
{
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
     * @inheritdoc
     */
    public function resolve(
        SearchRequestBuilderInterface $searchRequestBuilder,
        string $fieldName,
        string $argumentName,
        array $argument
    ): SearchRequestBuilderInterface {
        $productsInfo = [];
        foreach ($argument[$argumentName] as $productInfoData) {
            $productsInfo[] = $this->productInfoInterfaceFactory->create([self::SKU => $productInfoData[self::SKU]]);
        }
        $extension = $this->getExtensionAttributes($searchRequestBuilder);

        $extension->setProductsInfo($productsInfo);
        $searchRequestBuilder->setSearchRequestExtension($extension);

        return $searchRequestBuilder;
    }

    /**
     * Get Search Request Extension Attributes DTO.
     *
     * @param SearchRequestBuilderInterface $searchRequestBuilder
     *
     * @return SearchRequestExtensionInterface
     */
    private function getExtensionAttributes(
        SearchRequestBuilderInterface $searchRequestBuilder
    ): SearchRequestExtensionInterface {
        $builderCopy = clone $searchRequestBuilder;
        $searchRequest = $builderCopy->create();
        $extension = $searchRequest->getExtensionAttributes();

        if (!$extension) {
            $extension = $this->searchRequestExtensionFactory->create();
        }

        return $extension;
    }
}

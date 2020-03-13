<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model\SourceItem\Validator\ShipmentTypeValidator;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Retrieve bundle product by product sku service.
 */
class GetBundleProductsByChildSku
{
    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param Type $type
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        Type $type,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->type = $type;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
    }

    /**
     * Retrieve bundle products by child sku.
     *
     * @param string $sku
     * @return ProductInterface[]
     */
    public function execute(string $sku): array
    {
        try {
            $id = $this->getProductIdsBySkus->execute([$sku])[$sku];
        } catch (NoSuchEntityException $e) {
            return [];
        }
        $bundleProductIds = $this->type->getParentIdsByChild($id);
        if (!$bundleProductIds) {
            return [];
        }
        $criteria = $this->searchCriteriaBuilder->addFilter('entity_id', $bundleProductIds, 'in')->create();

        return $this->productRepository->getList($criteria)->getItems();
    }
}

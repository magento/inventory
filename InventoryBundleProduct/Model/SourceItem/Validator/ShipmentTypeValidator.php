<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model\SourceItem\Validator;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magento\InventoryApi\Model\SourceItemValidatorInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Validate source item for bundle product with shipment type "Ship Together".
 */
class ShipmentTypeValidator implements SourceItemValidatorInterface
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var GetSourceCodesBySkusInterface
     */
    private $getSourceCodesBySkus;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param ValidationResultFactory $validationResultFactory
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetSourceCodesBySkusInterface $getSourceCodesBySkus
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param UrlInterface $url
     * @param Type $type
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        ValidationResultFactory $validationResultFactory,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetSourceCodesBySkusInterface $getSourceCodesBySkus,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        UrlInterface $url,
        Type $type
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->validationResultFactory = $validationResultFactory;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->url = $url;
        $this->type = $type;
    }

    /**
     * Prevent add source to product if one is part of bundle product with shipment type "Ship Together".
     *
     * @param SourceItemInterface $sourceItem
     * @return ValidationResult
     */
    public function validate(SourceItemInterface $sourceItem): ValidationResult
    {
        $errors = [];
        if ($this->isSingleSourceMode->execute()) {
            return $this->validationResultFactory->create(['errors' => $errors]);
        }
        try {
            $sourceItemSku = $sourceItem->getSku();
            $id = $this->getProductIdsBySkus->execute([$sourceItemSku])[$sourceItemSku];
        } catch (NoSuchEntityException $e) {
            return $this->validationResultFactory->create(['errors' => $errors]);
        }
        $bundleProductIds = $this->type->getParentIdsByChild($id);
        if (!$bundleProductIds) {
            return $this->validationResultFactory->create(['errors' => $errors]);
        }
        $criteria = $this->searchCriteriaBuilder->addFilter('entity_id', $bundleProductIds, 'in')->create();
        $bundleProducts = $this->productRepository->getList($criteria);
        foreach ($bundleProducts->getItems() as $bundleProduct) {
            $sourceCodes = $this->getBundleProductSourceCodes($bundleProduct);
            if ($sourceCodes && !in_array($sourceItem->getSourceCode(), $sourceCodes)) {
                $url = $this->url->getUrl('catalog/product/edit', ['id' => $bundleProduct->getId()]);
                $errors[] = __(
                    'Not able to assign "%1" to product "%2", as it is part of bundle product'
                    . ' <a href="%3">"%4"</a> with shipment type "Ship Together" and has multiple sources '
                    . 'or different source as other bundle selections.',
                    $sourceItem->getSourceCode(),
                    $sourceItemSku,
                    $url,
                    $bundleProduct->getSku()
                );
                break;
            }
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * Get bundle product selections source codes.
     *
     * @param ProductInterface $bundleProduct
     * @return array
     */
    private function getBundleProductSourceCodes(ProductInterface $bundleProduct): array
    {
        if ((int)$bundleProduct->getShipmentType() === Type::SHIPMENT_SEPARATELY) {
            return [];
        }
        $options = $bundleProduct->getExtensionAttributes()->getBundleProductOptions();
        $skus = [];
        foreach ($options as $option) {
            foreach ($option->getProductLinks() as $link) {
                $skus[] = $link->getSku();
            }
        }

        return $this->getSourceCodesBySkus->execute(array_unique($skus));
    }
}

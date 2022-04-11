<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model\SourceItem\Validator;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magento\InventoryApi\Model\SourceItemValidatorInterface;
use Magento\InventoryBundleProduct\Model\GetBundleProductIdsByChildSku;
use Magento\InventoryBundleProduct\Model\GetChidrenSkusByParentIds;
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
     * @var GetSourceCodesBySkusInterface
     */
    private $getSourceCodesBySkus;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var GetBundleProductIdsByChildSku
     */
    private $getBundleProductIdsByChildSku;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var GetChidrenSkusByParentIds
     */
    private $getChidrenSkusByParentIds;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param ValidationResultFactory $validationResultFactory
     * @param GetSourceCodesBySkusInterface $getSourceCodesBySkus
     * @param UrlInterface $url
     * @param GetBundleProductIdsByChildSku $getBundleProductIdsByChildSku
     * @param CollectionFactory $productCollectionFactory
     * @param MetadataPool $metadataPool
     * @param GetChidrenSkusByParentIds $getChidrenSkusByParentIds
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        ValidationResultFactory $validationResultFactory,
        GetSourceCodesBySkusInterface $getSourceCodesBySkus,
        UrlInterface $url,
        GetBundleProductIdsByChildSku $getBundleProductIdsByChildSku,
        CollectionFactory $productCollectionFactory,
        MetadataPool $metadataPool,
        GetChidrenSkusByParentIds $getChidrenSkusByParentIds
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->validationResultFactory = $validationResultFactory;
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
        $this->url = $url;
        $this->getBundleProductIdsByChildSku = $getBundleProductIdsByChildSku;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->metadataPool = $metadataPool;
        $this->getChidrenSkusByParentIds = $getChidrenSkusByParentIds;
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
        $bundleProductIds = $this->getBundleProductIdsByChildSku->execute((string) $sourceItem->getSku());
        if (!$bundleProductIds) {
            return $this->validationResultFactory->create(['errors' => $errors]);
        }
        $collection = $this->getBundleProductsCollection($bundleProductIds);
        $shipTogetherBundleProductIdsByLinkId = $this->getShipTogetherBundleProductIdsByLinkId($collection);
        $chidrenSkusByParentId = $shipTogetherBundleProductIdsByLinkId
            ? $this->getChidrenSkusByParentIds->execute(array_keys($shipTogetherBundleProductIdsByLinkId))
            : [];
        foreach ($chidrenSkusByParentId as $bundleProductLinkId => $skus) {
            $sourceCodes = $this->getSourceCodesBySkus->execute($skus);
            if ($sourceCodes && !in_array($sourceItem->getSourceCode(), $sourceCodes)) {
                $bundleProduct = $collection->getItemById($shipTogetherBundleProductIdsByLinkId[$bundleProductLinkId]);
                $url = $this->url->getUrl('catalog/product/edit', ['id' => $bundleProduct->getId()]);
                $errors[] = __(
                    'Not able to assign "%1" to product "%2", as it is part of bundle product'
                    . ' <a href="%3">"%4"</a> with shipment type "Ship Together" and has multiple sources '
                    . 'or different source as other bundle selections.',
                    $sourceItem->getSourceCode(),
                    $sourceItem->getSku(),
                    $url,
                    $bundleProduct->getSku()
                );
                break;
            }
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * Return bundle products collection
     *
     * @param array $bundleProductIds
     * @return Collection
     */
    private function getBundleProductsCollection(array $bundleProductIds): Collection
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToFilter('entity_id', ['in' => $bundleProductIds])
            ->addAttributeToFilter('type_id', ['eq' => \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE])
            ->addAttributeToSelect('shipment_type');
        return $collection;
    }

    /**
     * Return bundle product IDs with shipment type "Ship Together"
     *
     * @param Collection $collection
     * @return array
     */
    private function getShipTogetherBundleProductIdsByLinkId(Collection $collection): array
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $shipTogetherBundleProductIdsByLinkId = [];
        foreach ($collection as $product) {
            if ((int)$product->getShipmentType() !== Type::SHIPMENT_SEPARATELY) {
                $shipTogetherBundleProductIdsByLinkId[$product->getData($metadata->getLinkField())] = $product->getId();
            }
        }
        return $shipTogetherBundleProductIdsByLinkId;
    }
}

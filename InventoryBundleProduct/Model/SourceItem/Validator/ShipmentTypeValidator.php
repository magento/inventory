<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model\SourceItem\Validator;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magento\InventoryApi\Model\SourceItemValidatorInterface;
use Magento\InventoryBundleProduct\Model\SourceItem\Validator\ShipmentTypeValidator\GetBundleProductsByChildSku;
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
     * @var GetBundleProductsByChildSku
     */
    private $bundleProductsByChildSku;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param ValidationResultFactory $validationResultFactory
     * @param GetSourceCodesBySkusInterface $getSourceCodesBySkus
     * @param UrlInterface $url
     * @param GetBundleProductsByChildSku $bundleProductsByChildSku
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        ValidationResultFactory $validationResultFactory,
        GetSourceCodesBySkusInterface $getSourceCodesBySkus,
        UrlInterface $url,
        GetBundleProductsByChildSku $bundleProductsByChildSku
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->validationResultFactory = $validationResultFactory;
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
        $this->url = $url;
        $this->bundleProductsByChildSku = $bundleProductsByChildSku;
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
        $products = $this->bundleProductsByChildSku->execute((string)$sourceItem->getSku());
        if (!$products) {
            return $this->validationResultFactory->create(['errors' => $errors]);
        }
        foreach ($products as $bundleProduct) {
            $sourceCodes = $this->getBundleProductSourceCodes($bundleProduct);
            if ($sourceCodes && !in_array($sourceItem->getSourceCode(), $sourceCodes)) {
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

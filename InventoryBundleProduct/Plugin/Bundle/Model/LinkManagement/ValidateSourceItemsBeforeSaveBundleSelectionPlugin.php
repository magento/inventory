<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Verify ability to save product as bundle selection plugin.
 */
class ValidateSourceItemsBeforeSaveBundleSelectionPlugin
{
    /**
     * @var GetSourceCodesBySkusInterface
     */
    private $getSourceCodesBySkus;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param GetSourceCodesBySkusInterface $getSourceCodesBySkus
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        GetSourceCodesBySkusInterface $getSourceCodesBySkus,
        ProductRepositoryInterface $productRepository
    ) {
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->productRepository = $productRepository;
    }

    /**
     * Validate source items before save product as bundle selection.
     *
     * @param ProductLinkManagementInterface $subject
     * @param string $sku
     * @param LinkInterface $link
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function beforeSaveChild(
        ProductLinkManagementInterface $subject,
        string $sku,
        LinkInterface $link
    ): void {
        $product = $this->productRepository->get($sku, true);
        if ($this->isSingleSourceMode->execute()
            || (int)$product->getShipmentType() === AbstractType::SHIPMENT_SEPARATELY) {
            return;
        }
        $skus = [$link->getSku()];
        foreach ($subject->getChildren($sku) as $child) {
            $skus[] = $child->getSku();
        }
        $sourceCodes = $this->getSourceCodesBySkus->execute($skus) ?: [];
        if (count($sourceCodes) > 1) {
            throw new InputException(
                __(
                    'Product "%1" cannot be added to bundle product as '
                    . 'bundle product has "Ship Bundle Items Together" and "%1" product assigned to multiple sources'
                    . ' or has different source then rest of bundle items',
                    [$link->getSku()]
                )
            );
        }
    }
}

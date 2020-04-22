<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Verify ability to add product to bundle selection plugin.
 */
class ValidateSourceItemsBeforeAddBundleSelectionPlugin
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
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param GetSourceCodesBySkusInterface $getSourceCodesBySkus
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        GetSourceCodesBySkusInterface $getSourceCodesBySkus
    ) {
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Validate source items before add product as selection to bundle product.
     *
     * @param ProductLinkManagementInterface $subject
     * @param ProductInterface $product
     * @param int $optionId
     * @param LinkInterface $link
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAddChild(
        ProductLinkManagementInterface $subject,
        ProductInterface $product,
        int $optionId,
        LinkInterface $link
    ): void {
        if ($this->isSingleSourceMode->execute()
            || (int)$product->getShipmentType() === AbstractType::SHIPMENT_SEPARATELY) {
            return;
        }

        $skus = $this->getBundleSelectionsSkus($subject, $product, $link);
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

    /**
     * Retrieve bundle selections skus.
     *
     * @param ProductLinkManagementInterface $subject
     * @param ProductInterface $product
     * @param LinkInterface $link
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function getBundleSelectionsSkus(
        ProductLinkManagementInterface $subject,
        ProductInterface $product,
        LinkInterface $link
    ): array {
        $skus = [];
        $bundleSelectionsData = $product->getBundleSelectionsData() ?: [];
        foreach ($bundleSelectionsData as $option) {
            foreach ($option as $selection) {
                if (empty($selection['sku'])) {
                    continue;
                }
                $skus[] = $selection['sku'];
            }
        }
        if (!$skus) {
            $skus = [$link->getSku()];
            $children = $subject->getChildren($product->getSku());
            foreach ($children as $child) {
                $skus[] = $child->getSku();
            }
        }

        return $skus;
    }
}

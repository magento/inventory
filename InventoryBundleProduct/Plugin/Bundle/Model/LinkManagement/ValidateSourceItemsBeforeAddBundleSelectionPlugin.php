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
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;

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
     * @param GetSourceCodesBySkusInterface $getSourceCodesBySkus
     */
    public function __construct(GetSourceCodesBySkusInterface $getSourceCodesBySkus)
    {
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAddChild(
        ProductLinkManagementInterface $subject,
        ProductInterface $product,
        $optionId,
        LinkInterface $link
    ): void {
        if ($product->getShipmentType() === null
            || (int)$product->getShipmentType() === AbstractType::SHIPMENT_SEPARATELY) {
            return;
        }
        $skus = [];
        $bundleSelectionsData = $product->getBundleSelectionsData();
        foreach ($bundleSelectionsData as $option) {
            foreach ($option as $selection) {
                $skus[] = $selection['sku'];
            }
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

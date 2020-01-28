<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductAdminUi\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\InputException;
use Magento\InventoryBundleProductAdminUi\Model\ProcessSourceItemsForSku;

/**
 * Process source items after bundle link has been added plugin.
 */
class ProcessSourceItemsAfterAddBundleSelectionPlugin
{
    /**
     * @var ProcessSourceItemsForSku
     */
    private $processSourceItemsForSku;

    /**
     * @param ProcessSourceItemsForSku $processSourceItemsForSku
     */
    public function __construct(ProcessSourceItemsForSku $processSourceItemsForSku)
    {
        $this->processSourceItemsForSku = $processSourceItemsForSku;
    }

    /**
     * Process source items after selection has been added to bundle product.
     *
     * @param ProductLinkManagementInterface $subject
     * @param int $result
     * @param ProductInterface $product
     * @param int $optionId
     * @param LinkInterface $linkedProduct
     * @return int
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddChild(
        ProductLinkManagementInterface $subject,
        int $result,
        ProductInterface $product,
        $optionId,
        LinkInterface $linkedProduct
    ): int {
        $this->processSourceItemsForSku->execute((string)$linkedProduct->getSku());

        return $result;
    }
}

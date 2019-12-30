<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductAdminUi\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\InventoryBundleProductAdminUi\Model\ProcessSourceItemsForSku;

/**
 * Process source items after bundle link has been added by product sku plugin.
 */
class ProcessSourceItemsAfterAddBundleSelectionBySkuPlugin
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
     * Process source items after bundle selection has been added by product sku.
     *
     * @param ProductLinkManagementInterface $subject
     * @param int $result
     * @param string $sku
     * @param int $optionId
     * @param LinkInterface $linkedProduct
     * @return int
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddChildByProductSku(
        ProductLinkManagementInterface $subject,
        int $result,
        $sku,
        $optionId,
        LinkInterface $linkedProduct
    ): int {
        $this->processSourceItemsForSku->execute((string)$linkedProduct->getSku());

        return $result;
    }
}

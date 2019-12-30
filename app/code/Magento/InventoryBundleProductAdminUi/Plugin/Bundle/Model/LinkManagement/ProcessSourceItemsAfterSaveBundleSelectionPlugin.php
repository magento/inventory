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
 * Process source items after bundle link has been saved plugin.
 */
class ProcessSourceItemsAfterSaveBundleSelectionPlugin
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
     * Process source items after bundle selection has been updated.
     *
     * @param ProductLinkManagementInterface $subject
     * @param bool $result
     * @param string $sku
     * @param LinkInterface $linkedProduct
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws InputException
     */
    public function afterSaveChild(
        ProductLinkManagementInterface $subject,
        bool $result,
        $sku,
        LinkInterface $linkedProduct
    ): bool {
        $this->processSourceItemsForSku->execute((string)$linkedProduct->getSku());

        return $result;
    }
}

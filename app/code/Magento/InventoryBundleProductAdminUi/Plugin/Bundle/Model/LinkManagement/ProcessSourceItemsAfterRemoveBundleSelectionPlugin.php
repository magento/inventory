<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductAdminUi\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\InventoryBundleProductAdminUi\Model\ProcessSourceItemsForSku;

/**
 * Process source items after bundle link has been removed plugin.
 */
class ProcessSourceItemsAfterRemoveBundleSelectionPlugin
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
     * Process source items after bundle selection has been removed.
     *
     * @param ProductLinkManagementInterface $subject
     * @param bool $result
     * @param string $sku
     * @param string $optionId
     * @param string $childSku
     * @return bool
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRemoveChild(
        ProductLinkManagementInterface $subject,
        bool $result,
        $sku,
        $optionId,
        $childSku
    ): bool {
        $this->processSourceItemsForSku->execute((string)$childSku);

        return $result;
    }
}

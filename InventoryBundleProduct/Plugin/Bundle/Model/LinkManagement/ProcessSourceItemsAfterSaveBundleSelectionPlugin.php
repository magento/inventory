<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\Bundle\Model\LinkManagement;

use Magento\Bundle\Api\Data\LinkInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\InventoryBundleProduct\Model\ProcessSourceItemsForSku;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProcessSourceItemsForSku $processSourceItemsForSku
     * @param LoggerInterface $logger
     */
    public function __construct(ProcessSourceItemsForSku $processSourceItemsForSku, LoggerInterface $logger)
    {
        $this->processSourceItemsForSku = $processSourceItemsForSku;
        $this->logger = $logger;
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
     */
    public function afterSaveChild(
        ProductLinkManagementInterface $subject,
        bool $result,
        $sku,
        LinkInterface $linkedProduct
    ): bool {
        try {
            $this->processSourceItemsForSku->execute((string)$linkedProduct->getSku());
        } catch (InputException $e) {
            $this->logger->error($e->getLogMessage());
        }

        return $result;
    }
}

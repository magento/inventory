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
use Magento\Framework\Exception\InputException;
use Magento\InventoryBundleProduct\Model\ProcessSourceItemsForSku;
use Psr\Log\LoggerInterface;

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
     * Process source items after selection has been added to bundle product.
     *
     * @param ProductLinkManagementInterface $subject
     * @param int $result
     * @param ProductInterface $product
     * @param int $optionId
     * @param LinkInterface $linkedProduct
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddChild(
        ProductLinkManagementInterface $subject,
        int $result,
        ProductInterface $product,
        $optionId,
        LinkInterface $linkedProduct
    ): int {
        try {
            $this->processSourceItemsForSku->execute((string)$linkedProduct->getSku());
        } catch (InputException $e) {
            $this->logger->error($e->getLogMessage());
        }

        return $result;
    }
}

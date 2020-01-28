<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ProductRepository;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\InventoryCatalogApi\Model\DeleteSourceItemsBySkusInterface;
use Psr\Log\LoggerInterface;

/**
 * Remove source items after given product has been deleted plugin.
 */
class DeleteSourceItemsPlugin
{
    /**
     * @var DeleteSourceItemsBySkusInterface
     */
    private $deleteSourceItemsBySkus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @param DeleteSourceItemsBySkusInterface $deleteSourceItemsBySkus
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        DeleteSourceItemsBySkusInterface $deleteSourceItemsBySkus,
        LoggerInterface $logger,
        ManagerInterface $messageManager
    ) {
        $this->deleteSourceItemsBySkus = $deleteSourceItemsBySkus;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    /**
     * Delete source items after product has been deleted.
     *
     * @param ProductRepositoryInterface $subject
     * @param bool $result
     * @param ProductInterface $product
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(ProductRepositoryInterface $subject, $result, ProductInterface $product): bool
    {
        $deleteResult = $this->deleteSourceItemsBySkus->execute([(string)$product->getSku()]);
        if (!$deleteResult->isSuccessful()) {
            $failed = $deleteResult->getFailed();
            $failedSkus = [];
            foreach ($failed as $fail) {
                $failedSkus[] = $fail['sku'];
            }
            $error = current($failed);
            $message = $error['message'] . ' sku(s): ' . implode(', ', $failedSkus);
            $this->logger->critical($message);
            $this->messageManager->addErrorMessage($message);
        }

        return $result;
    }
}

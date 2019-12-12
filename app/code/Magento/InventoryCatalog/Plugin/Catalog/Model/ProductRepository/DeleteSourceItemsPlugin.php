<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ProductRepository;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
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
     * @param DeleteSourceItemsBySkusInterface $deleteSourceItemsBySkus
     * @param LoggerInterface $logger
     */
    public function __construct(
        DeleteSourceItemsBySkusInterface $deleteSourceItemsBySkus,
        LoggerInterface $logger
    ) {
        $this->deleteSourceItemsBySkus = $deleteSourceItemsBySkus;
        $this->logger = $logger;
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
        try {
            $this->deleteSourceItemsBySkus->execute([(string)$product->getSku()]);
        } catch (CouldNotSaveException|InputException $e) {
            $this->logger->error(
                __(
                    'Not able to delete source items for product: %productSku. ' . $e->getMessage(),
                    ['productSku' => $product->getSku()]
                )
            );
        }

        return $result;
    }
}

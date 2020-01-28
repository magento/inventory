<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalog\Model\DeleteSourceItemsBySkus;
use Psr\Log\LoggerInterface;

/**
 * Process source items after product save.
 */
class ProcessSourceItemsPlugin
{
    /**
     * @var DeleteSourceItemsBySkus
     */
    private $deleteSourceItemsBySkus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @param DeleteSourceItemsBySkus $deleteSourceItemsBySkus
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param LoggerInterface $logger
     */
    public function __construct(
        DeleteSourceItemsBySkus $deleteSourceItemsBySkus,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        LoggerInterface $logger
    ) {
        $this->deleteSourceItemsBySkus = $deleteSourceItemsBySkus;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->logger = $logger;
    }

    /**
     * Delete source items in case product sku or type has been changed.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        Product $subject,
        Product $result,
        AbstractModel $product
    ): Product {
        if ($this->areSourceItemsShouldBeDeleted($product)) {
            try {
                $this->deleteSourceItemsBySkus->execute([(string)$product->getOrigData('sku')]);
            } catch (CouldNotSaveException|InputException $e) {
                $this->logger->error(
                    __(
                        'Not able to delete source items for product: %productSku. ' . $e->getMessage(),
                        ['productSku' => $product->getSku()]
                    )
                );
            }
        }

        return $result;
    }

    /**
     * Verify, if source items for given product should be deleted.
     *
     * @param AbstractModel $product
     * @return bool
     */
    private function areSourceItemsShouldBeDeleted(AbstractModel $product): bool
    {
        $origSku = $product->getOrigData('sku');
        $origType = $product->getOrigData('type_id');

        return $origSku !== null && $origSku !== $product->getSku()
            || $origType !== null && $origType !== $product->getTypeId();
    }
}

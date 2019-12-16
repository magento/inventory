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
use Magento\InventoryCatalog\Model\IsSingleSourceMode;
use Magento\InventoryCatalog\Model\ResourceModel\UpdateSourceItemsBySku;
use Psr\Log\LoggerInterface;

/**
 * Process source items after product save.
 */
class ProcessSourceItemsPlugin
{
    /**
     * @var IsSingleSourceMode
     */
    private $isSingleSourceMode;

    /**
     * @var UpdateSourceItemsBySku
     */
    private $updateSourceItemsBySku;

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
     * @param IsSingleSourceMode $isSingleSourceMode
     * @param UpdateSourceItemsBySku $updateSourceItemsBySku
     * @param DeleteSourceItemsBySkus $deleteSourceItemsBySkus
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param LoggerInterface $logger
     */
    public function __construct(
        IsSingleSourceMode $isSingleSourceMode,
        UpdateSourceItemsBySku $updateSourceItemsBySku,
        DeleteSourceItemsBySkus $deleteSourceItemsBySkus,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        LoggerInterface $logger
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->updateSourceItemsBySku = $updateSourceItemsBySku;
        $this->deleteSourceItemsBySkus = $deleteSourceItemsBySkus;
        $this->logger = $logger;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
    }

    /**
     * Update reservations in case product sku has been changed.
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
        if ($this->isSingleSourceMode->execute()) {
            return $result;
        }

        $origSku = $product->getOrigData('sku');
        if ($origSku !== null && $origSku !== $product->getSku()) {
            $this->updateSourceItemsBySku->execute($origSku, $product->getSku());
        }

        if ($product->getOrigData('type_id') !== null && $product->getOrigData('type_id') !== $product->getTypeId()) {
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
        }

        return $result;
    }
}

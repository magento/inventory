<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProductAdminUi\Plugin\Catalog\Model\Product\Link;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Link;
use Magento\Framework\Exception\InputException;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupedProductType;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * After save source links process child source items for reindex grouped product inventory.
 */
class ProcessSourceItemsAfterSaveAssociatedLinks
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemsProcessorInterface
     */
    private $sourceItemsProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SourceItemsProcessorInterface $sourceItemsProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SourceItemsProcessorInterface $sourceItemsProcessor,
        LoggerInterface $logger
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemsProcessor = $sourceItemsProcessor;
        $this->logger = $logger;
    }

    /**
     * Process source items for 'associated' linked products.
     *
     * @param Link $subject
     * @param Link $result
     * @param ProductInterface $product
     * @return Link
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSaveProductRelations(
        Link $subject,
        Link $result,
        ProductInterface $product
    ): Link {
        if ($product->getTypeId() !== GroupedProductType::TYPE_CODE) {
            return $result;
        }

        foreach ($product->getProductLinks() as $productLink) {
            if ($productLink->getLinkType() === 'associated') {
                $this->processSourceItemsForSku($productLink->getLinkedProductSku());
            }
        }

        return $result;
    }

    /**
     * Load source items data from assigned products and process this items.
     *
     * @param string $sku
     * @return void
     */
    private function processSourceItemsForSku(string $sku): void
    {
        $processData = [];

        foreach ($this->getSourceItemsBySku->execute($sku) as $sourceItem) {
            $processData[] = [
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                SourceItemInterface::STATUS => $sourceItem->getStatus(),
            ];
        }

        if (!empty($processData)) {
            try {
                $this->sourceItemsProcessor->execute((string)$sku, $processData);
            } catch (InputException $e) {
                $this->logger->error($e->getLogMessage());
            }
        }
    }
}

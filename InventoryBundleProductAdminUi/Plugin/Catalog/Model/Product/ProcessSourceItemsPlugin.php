<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductAdminUi\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Process source items after bundle product save.
 */
class ProcessSourceItemsPlugin
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
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SourceItemsProcessorInterface $processSourceItemsForSku
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SourceItemsProcessorInterface $processSourceItemsForSku,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        LoggerInterface $logger
    ) {
        $this->sourceItemsProcessor = $processSourceItemsForSku;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->logger = $logger;
    }

    /**
     * Process source items for bundle selections after bundle product has been saved.
     *
     * @param Product $subject
     * @param Product $result
     * @return Product
     */
    public function afterAfterSave(Product $subject, Product $result): Product
    {
        if ($subject->getTypeId() !== Type::TYPE_BUNDLE) {
            return $result;
        }

        $options = $result->getBundleSelectionsData() ?: [];
        foreach ($options as $option) {
            foreach ($option as $selection) {
                try {
                    $sku = $this->getSelectionSku($selection);
                    if ($sku) {
                        $this->sourceItemsProcessor->execute(
                            $sku,
                            $this->getSourceItemsData($sku)
                        );
                    }
                } catch (InputException|NoSuchEntityException $e) {
                    $this->logger->error($e->getLogMessage());
                    continue;
                }
            }
        }

        return $result;
    }

    /**
     * Get source items as array data for given sku.
     *
     * @param string $sku
     * @return array
     */
    private function getSourceItemsData(string $sku): array
    {
        $data = [];
        foreach ($this->getSourceItemsBySku->execute($sku) as $sourceItem) {
            $data[] = [
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                SourceItemInterface::STATUS => $sourceItem->getStatus(),
            ];
        }

        return $data;
    }

    /**
     * Retrieve bundle selection sku.
     *
     * @param array $selection
     * @return string
     * @throws NoSuchEntityException
     */
    private function getSelectionSku(array $selection): string
    {
        $sku = $selection['sku'] ?? '';

        if (!$sku && isset($selection['product_id'])) {
            $sku =$this->getSkusByProductIds->execute([$selection['product_id']])[$selection['product_id']];
        }

        return $sku;
    }
}

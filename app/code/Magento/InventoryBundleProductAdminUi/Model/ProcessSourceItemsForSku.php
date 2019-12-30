<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductAdminUi\Model;

use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogAdminUi\Observer\SourceItemsProcessor;

/**
 * Process source items for given bundle selection service.
 */
class ProcessSourceItemsForSku
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemsProcessor
     */
    private $sourceItemsProcessor;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SourceItemsProcessor $sourceItemsProcessor
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SourceItemsProcessor $sourceItemsProcessor
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemsProcessor = $sourceItemsProcessor;
    }

    /**
     * Process source items for given bundle selection sku.
     *
     * @param string $sku
     * @throws InputException
     */
    public function execute(string $sku)
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
            $this->sourceItemsProcessor->process($sku, $processData);
        }
    }
}

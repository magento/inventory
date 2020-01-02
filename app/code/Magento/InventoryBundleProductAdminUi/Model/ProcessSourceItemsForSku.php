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
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

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
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SourceItemsProcessor $sourceItemsProcessor
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SourceItemsProcessor $sourceItemsProcessor,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemsProcessor = $sourceItemsProcessor;
        $this->defaultSourceProvider = $defaultSourceProvider;
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
            if ($sourceItem->getSourceCode() === $this->defaultSourceProvider->getCode()) {
                continue;
            }
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

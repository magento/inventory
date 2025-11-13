<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

class SetQtyToLegacyStock
{
    /**
     * @param RequestInterface $request
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly DefaultSourceProviderInterface $defaultSourceProvider
    ) {
    }

    /**
     * Set qty to legacy stock.
     *
     * @param StockRegistryInterface $subject
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeUpdateStockItemBySku(
        StockRegistryInterface $subject,
        string $productSku,
        StockItemInterface $stockItem
    ): array {
        $sources = $this->request->getParam('sources', []);
        if (isset($sources['assigned_sources']) && is_array($sources['assigned_sources'])) {
            $defaultSourceCode = $this->defaultSourceProvider->getCode();
            foreach ($sources['assigned_sources'] as $source) {
                if ($source['source_code'] === $defaultSourceCode) {
                    if (isset($source['quantity']) || isset($source['qty'])) {
                        $stockItem->setQty($source['quantity'] ?? $source['qty']);
                    }
                    if (isset($source['status']) || isset($source['source_status'])) {
                        $stockItem->setIsInStock($source['status'] ?? $source['source_status']);
                    }
                    break;
                }
            }
        }
        return [$productSku, $stockItem];
    }
}

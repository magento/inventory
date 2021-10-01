<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\RequestInterface;

class SetQtyToLegacyStock
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
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
            foreach ($sources['assigned_sources'] as $source) {
                if ($source['name'] === 'Default Source') {
                    $stockItem->setQty($source['quantity']);
                    break;
                }
            }
        }
        return [$productSku, $stockItem];
    }
}

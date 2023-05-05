<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Api\StockRegistry;

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
        $configurableOptions = $this->request->getParam('quantity_resolver', []);
        if (isset($configurableOptions['dynamicRows']['dynamicRows'])
            && is_array($configurableOptions['dynamicRows']['dynamicRows'])
        ) {
            foreach ($configurableOptions['dynamicRows']['dynamicRows'] as $source) {
                if ($source['source'] === 'Default Source') {
                    $stockItem->setQty($source['quantity_per_source']);
                    break;
                }
            }
        }
        return [$productSku, $stockItem];
    }
}

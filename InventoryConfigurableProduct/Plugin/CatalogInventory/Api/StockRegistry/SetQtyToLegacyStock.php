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
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;

class SetQtyToLegacyStock
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var GetLegacyStockItem
     */
    private GetLegacyStockItem $getLegacyStockItem;

    /**
     * @param RequestInterface $request
     * @param GetLegacyStockItem $getLegacyStockItem
     */
    public function __construct(RequestInterface $request, GetLegacyStockItem $getLegacyStockItem)
    {
        $this->request = $request;
        $this->getLegacyStockItem = $getLegacyStockItem;
    }

    /**
     * Set qty to legacy stock.
     *
     * @param StockRegistryInterface $subject
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
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
        $this->getLegacyStockItem->execute($productSku);
        return [$productSku, $stockItem];
    }
}

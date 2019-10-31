<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupFrontend\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Type\Onepage;

/**
 * Provide product sku for custom options.
 */
class ProductOptionSkuConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Onepage
     */
    private $onePage;

    /**
     * @param Onepage $onePage
     */
    public function __construct(Onepage $onePage)
    {
        $this->onePage = $onePage;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $output['quoteItemData'] = $this->getCustomOptionsSku();

        return $output;
    }

    /**
     * Retrieve products skus from custom options.
     *
     * @return array
     */
    private function getCustomOptionsSku(): array
    {
        $output = [];
        $quote = $this->onePage->getQuote();
        $quoteItems = $quote->getItems();
        $allItems = $quote->getItemsCollection()->getItems();
        foreach ($quoteItems as $item) {
            $qtyOptions = $item->getQtyOptions() ?: [];
            foreach ($qtyOptions as $option) {
                $productId = (int)$option->getProductId();
                foreach ($allItems as $quoteItem) {
                    if ((int)$quoteItem->getProductId() === $productId) {
                        $output['customOptionsSkuData'] = [
                            'item_id' => $item->getId(),
                            'sku' => $quoteItem->getSku(),
                        ];
                    }
                }
            }
        }

        return $output;
    }
}

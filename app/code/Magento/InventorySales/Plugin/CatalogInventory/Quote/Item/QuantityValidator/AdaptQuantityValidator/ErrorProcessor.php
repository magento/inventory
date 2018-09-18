<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator\AdaptQuantityValidator;

use Magento\Framework\DataObject;
use Magento\CatalogInventory\Helper\Data;

/**
 * Process quantity errors for quote and quote item.
 */
class ErrorProcessor
{
    /**
     * Add error information to Quote Item.
     *
     * @param DataObject $result
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addErrorInfoToQuote(DataObject $result, $quoteItem)
    {
        $quoteItem->addErrorInfo(
            'cataloginventory',
            Data::ERROR_QTY,
            $result->getMessage()
        );

        $quoteItem->getQuote()->addErrorInfo(
            $result->getQuoteMessageIndex(),
            'cataloginventory',
            Data::ERROR_QTY,
            $result->getQuoteMessage()
        );
    }

    /**
     * Removes error statuses from quote and item.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param int $code
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeErrorsFromQuoteAndItem($item, int $code) :void
    {
        if ($item->getHasError()) {
            $params = ['origin' => 'cataloginventory', 'code' => $code];
            $item->removeErrorInfosByParams($params);
        }

        $quote = $item->getQuote();
        if ($quote->getHasError()) {
            $quoteItems = $quote->getItemsCollection();
            $canRemoveErrorFromQuote = true;
            foreach ($quoteItems as $quoteItem) {
                if ($quoteItem->getItemId() == $item->getItemId()) {
                    continue;
                }

                $errorInfos = $quoteItem->getErrorInfos();
                foreach ($errorInfos as $errorInfo) {
                    if ($errorInfo['code'] == $code) {
                        $canRemoveErrorFromQuote = false;
                        break;
                    }
                }

                if (!$canRemoveErrorFromQuote) {
                    break;
                }
            }

            if ($canRemoveErrorFromQuote) {
                $params = ['origin' => 'cataloginventory', 'code' => $code];
                $quote->removeErrorInfosByParams(null, $params);
            }
        }
    }
}

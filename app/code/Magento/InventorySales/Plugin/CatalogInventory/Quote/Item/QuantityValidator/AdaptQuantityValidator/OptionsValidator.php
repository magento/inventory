<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator\AdaptQuantityValidator;

use Magento\CatalogInventory\Helper\Data;
use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\Option;

/**
 * Check requested quantity is available for given quote item options.
 */
class OptionsValidator
{
    /**
     * @var Option
     */
    private $optionInitializer;

    /**
     * @var ErrorProcessor
     */
    private $errorProcessor;

    /**
     * @param Option $optionInitializer
     * @param ErrorProcessor $errorProcessor
     */
    public function __construct(
        Option $optionInitializer,
        ErrorProcessor $errorProcessor
    ) {
        $this->optionInitializer = $optionInitializer;
        $this->errorProcessor = $errorProcessor;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param float $qty
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($quoteItem, float $qty): void
    {
        $options = $quoteItem->getQtyOptions();
        foreach ($options as $option) {
            $this->optionInitializer->initialize($option, $quoteItem, $qty);
        }
        $removeError = true;
        foreach ($options as $option) {
            $result = $option->getStockStateResult();
            if ($result->getHasError()) {
                $option->setHasError(true);
                $removeError = false;
                $this->errorProcessor->addErrorInfoToQuote($result, $quoteItem);
            }
        }
        if ($removeError) {
            $this->errorProcessor->removeErrorsFromQuoteAndItem($quoteItem, Data::ERROR_QTY);
        }
    }
}

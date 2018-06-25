<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator\AdaptQuantityValidator;

use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\CatalogInventory\Helper\Data;

/**
 * Check requested quantity available for given quote item.
 */
class ItemValidator
{
    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var ErrorProcessor
     */
    private $errorProcessor;

    /**
     * @var array
     */
    private $validationResults = [];

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param ObjectFactory $objectFactory
     * @param ErrorProcessor $errorProcessor
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        ObjectFactory $objectFactory,
        ErrorProcessor $errorProcessor
    ) {
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->objectFactory = $objectFactory;
        $this->errorProcessor = $errorProcessor;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param float $qty
     * @param string $sku
     * @param int $stockId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($quoteItem, float $qty, string $sku, int $stockId): void
    {
        $key = $stockId . '_' . $sku . '_' . $qty;

        if (isset($this->validationResults[$key])) {
            return;
        }

        $result = $this->objectFactory->create();
        $result->setHasError(false);

        $qty = $quoteItem->getParentItem() ? $quoteItem->getParentItem()->getQty() * $qty : $qty;
        $isSalableResult = $this->isProductSalableForRequestedQty->execute($sku, $stockId, $qty);
        foreach ($isSalableResult->getErrors() as $error) {
            $result->setHasError(true)->setMessage($error->getMessage())->setQuoteMessage($error->getMessage())
                ->setQuoteMessageIndex('qty');
        }
        $this->validationResults[$key] = $result;

        if ($result->getHasError()) {
            $this->errorProcessor->addErrorInfoToQuote($result, $quoteItem);
        } else {
            $this->errorProcessor->removeErrorsFromQuoteAndItem($quoteItem, Data::ERROR_QTY);
        }
    }
}

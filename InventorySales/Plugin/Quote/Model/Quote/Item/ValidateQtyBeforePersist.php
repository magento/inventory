<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Quote\Model\Quote\Item;

use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item;

/**
 * Validate cart item qty before persist.
 */
class ValidateQtyBeforePersist
{
    /**
     * @var QuantityValidator
     */
    private $qtyValidator;

    /**
     * @var ObserverFactory
     */
    private $observerFactory;

    /**
     * @param QuantityValidator $validator
     * @param ObserverFactory $observerFactory
     */
    public function __construct(QuantityValidator $validator, ObserverFactory $observerFactory)
    {
        $this->qtyValidator = $validator;
        $this->observerFactory = $observerFactory;
    }

    /**
     * Validate item qty before save.
     *
     * @param Item $subject
     * @return void
     * @throws LocalizedException
     */
    public function beforeBeforeSave(Item $subject): void
    {
        $quote = $subject->getQuote();
        if ($quote->getIsActive() === false) {
            return;
        }
        $event = $this->observerFactory->create(Event::class, ['data' => ['item' => $subject]]);
        $observer = $this->observerFactory->create(Observer::class, ['data' => ['event' => $event]]);
        $this->qtyValidator->validate($observer);
        if (!empty($subject->getMessage()) && $subject->getHasError()) {
            throw new LocalizedException(__($subject->getMessage()));
        }
    }
}

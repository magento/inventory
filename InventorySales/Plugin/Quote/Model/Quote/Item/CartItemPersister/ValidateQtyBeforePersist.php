<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Quote\Model\Quote\Item\CartItemPersister;

use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item\CartItemPersister;

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
     * @param CartItemPersister $subject
     * @param CartInterface $quote
     * @param CartItemInterface $item
     *
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(CartItemPersister $subject, CartInterface $quote, CartItemInterface $item): array
    {
        /** @var Event $event */
        $event = $this->observerFactory->create(Event::class, ['data' => ['item' => $item]]);
        /** @var Observer $observer */
        $observer = $this->observerFactory->create(Observer::class, ['data' => ['event' => $event]]);

        $this->qtyValidator->validate($observer);
        if (!empty($item->getMessage()) && $item->getHasError()) {
            throw new LocalizedException(__($item->getMessage()));
        }

        return [
            $quote,
            $item
        ];
    }
}

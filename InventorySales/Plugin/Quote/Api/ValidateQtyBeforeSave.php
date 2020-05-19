<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Quote\Api;

use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;

/**
 * Validate item qty before save.
 */
class ValidateQtyBeforeSave
{
    /**
     * @var QuantityValidator
     */
    private $validator;

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
        $this->validator = $validator;
        $this->observerFactory = $observerFactory;
    }

    /**
     * @param CartItemRepositoryInterface $subject
     * @param CartItemInterface $item
     *
     * @return array
     * @throws LocalizedException
     */
    public function beforeSave(CartItemRepositoryInterface $subject, CartItemInterface $item): array
    {
        /** @var Event $event */
        $event = $this->observerFactory->create(Event::class, ['data' => ['item' => $item]]);
        /** @var Observer $observer */
        $observer = $this->observerFactory->create(Observer::class, ['data' => ['event' => $event]]);

        $this->validator->validate($observer);

        if (!empty($item->getMessage()) && $item->getHasError()) {
            throw new LocalizedException(__($item->getMessage()));
        }

        return [$item];
    }
}

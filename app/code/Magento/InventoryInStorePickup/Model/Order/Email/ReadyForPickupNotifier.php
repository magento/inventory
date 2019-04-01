<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Order\Email;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class ReadyForPickupNotifier
 *
 * @package Magento\InventoryInStorePickup\Model\Order\Email
 * TODO: probaly remove this class
 */
class ReadyForPickupNotifier extends \Magento\Sales\Model\AbstractNotifier
{
    /**
     * @var CollectionFactory
     */
    protected $historyCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var OrderSender
     */
    protected $sender;

    /**
     * @param CollectionFactory                                                      $historyCollectionFactory
     * @param Logger                                                                 $logger
     * @param \Magento\InventoryInStorePickup\Model\Order\Email\ReadyForPickupSender $sender
     */
    public function __construct(
        CollectionFactory $historyCollectionFactory,
        Logger $logger,
        ReadyForPickupSender $sender
    ) {
        $this->historyCollectionFactory = $historyCollectionFactory;
        $this->logger = $logger;
        $this->sender = $sender;
    }
}

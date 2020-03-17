<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\Order\Email;

use Magento\Sales\Model\AbstractModel;
use Magento\Sales\Model\AbstractNotifier;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * @inheritdoc
 */
class ReadyForPickupNotifier extends AbstractNotifier
{
    /**
     * @var CollectionFactory
     */
    protected $historyCollectionFactory;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var OrderSender
     */
    protected $sender;

    /**
     * @param CollectionFactory $historyCollectionFactory
     * @param Logger $logger
     * @param ReadyForPickupSender $sender
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

    /**
     * Notify user, order has been notified for pickup.
     *
     * @param AbstractModel $model
     * @return bool
     * @throws \Exception
     */
    public function notify(AbstractModel $model)
    {
        $this->sender->send($model);
        if (!$model->getExtensionAttributes()->getNotificationSent()) {
            return false;
        }
        $historyItem = $this->historyCollectionFactory->create()->getUnnotifiedForInstance($model);
        if ($historyItem) {
            $historyItem->setIsCustomerNotified(1);
            $historyItem->save();
        }

        return true;
    }
}

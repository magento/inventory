<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupSales\Model\Order\Email\ReadyForPickupSender;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Asynchronous email sending for notify order for pickup handler.
 */
class EmailSenderHandler
{
    /**
     * @var ReadyForPickupSender
     */
    private $emailSender;

    /**
     * @var Order
     */
    private $orderResource;

    /**
     * @var CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var IdentityInterface
     */
    private $identityContainer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ReadyForPickupSender $emailSender
     * @param Order $orderResource
     * @param CollectionFactory $orderCollectionFactory
     * @param ScopeConfigInterface $config
     * @param IdentityInterface|null $identityContainer
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ReadyForPickupSender $emailSender,
        Order $orderResource,
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $config,
        IdentityInterface $identityContainer,
        StoreManagerInterface $storeManager
    ) {
        $this->emailSender = $emailSender;
        $this->orderResource = $orderResource;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->config = $config;
        $this->identityContainer = $identityContainer;
        $this->storeManager = $storeManager;
    }

    /**
     * Handles asynchronous email sending for notify order for pickup.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function sendEmails(): void
    {
        if ($this->config->getValue('sales_email/general/async_sending')) {
            $collection = $this->orderCollectionFactory->create();
            $collection->addFieldToFilter('send_notification', ['eq' => 1]);
            $collection->addFieldToFilter('notification_sent', ['eq' => 0]);
            $collection->setPageSize(
                $this->config->getValue('sales_email/general/sending_limit')
            );
            $stores = $this->getStores(clone $collection);
            foreach ($stores as $store) {
                $this->identityContainer->setStore($store);
                if (!$this->identityContainer->isEnabled()) {
                    continue;
                }
                $orderCollection = clone $collection;
                $orderCollection->addFieldToFilter('store_id', $store->getId());
                foreach ($orderCollection->getItems() as $order) {
                    $this->emailSender->send($order, true);
                }
            }
        }
    }

    /**
     * Get stores for given orders.
     *
     * @param Collection $collection
     * @return StoreInterface[]
     * @throws NoSuchEntityException
     */
    private function getStores(Collection $collection): array
    {
        $stores = [];
        $collection->addAttributeToSelect('store_id')->getSelect()->group('store_id');
        foreach ($collection->getItems() as $item) {
            $store = $this->storeManager->getStore($item->getStoreId());
            $stores[$item->getStoreId()] = $store;
        }

        return $stores;
    }
}

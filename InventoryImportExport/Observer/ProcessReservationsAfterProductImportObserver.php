<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;

/**
 * Process reservations after products removed during import observer.
 */
class ProcessReservationsAfterProductImportObserver implements ObserverInterface
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @param PublisherInterface $publisher
     * @param ScopeConfigInterface $config
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        PublisherInterface $publisher,
        ScopeConfigInterface $config,
        GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
        $this->publisher = $publisher;
        $this->config = $config;
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * Asynchronously update/delete reservations after products have been removed during import.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->config->getValue('cataloginventory/options/synchronize_with_catalog')) {
            return;
        }

        $skus = [];
        $bunch = $observer->getEvent()->getData('bunch');
        foreach ($bunch as $product) {
            if (isset($product['sku'])) {
                $skus[] = $product['sku'];
            }
        }
        $this->publisher->publish('inventory.reservations.cleanup', $skus);
    }
}

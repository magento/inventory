<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;

/**
 * Synchronize quantity information which based on the CatalogInventory qty with
 * the new MSI default stock in order to have same behaviour for single stock shops
 */
class SaveInventoryDataObserver implements ObserverInterface
{
    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws InputException (thrown by SourceItemsSaveInterface)
     * @throws CouldNotSaveException (thrown by SourceItemsSaveInterface)
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        $extendedAttributes = $product->getExtensionAttributes();
        $stockItem = $extendedAttributes->getStockItem();

        if (!$stockItem) {
            return;
        }

        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceId($this->defaultSourceProvider->getId());
        $sourceItem->setSku($product->getSku());
        $sourceItem->setQuantity((float)$stockItem->getQty());
        $sourceItem->setStatus((bool)$stockItem->getIsInStock());

        $this->sourceItemsSave->execute([$sourceItem]);
    }
}

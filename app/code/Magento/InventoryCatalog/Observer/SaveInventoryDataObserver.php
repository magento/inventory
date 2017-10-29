<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryCatalog\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        DefaultSourceProviderInterface $defaultSourceProvider,
        ProductRepositoryInterface $productRepository
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws InputException (thrown by SourceItemsSaveInterface)
     * @throws CouldNotSaveException (thrown by SourceItemsSaveInterface)
     * @throws NoSuchEntityException (thrown by ProductRepositoryInterface)
     */
    public function execute(Observer $observer)
    {
        /** @var Item $item */
        $item = $observer->getEvent()->getData('item');

        /** @var ProductInterface $product */
        $product = $this->productRepository->getById($item->getProductId());
        $extendedAttributes = $product->getExtensionAttributes();
        if (!$extendedAttributes) {
            return;
        }

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

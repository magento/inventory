<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Inventory\Model\SourceItem\Command\SourceItemsDelete as SourceItemsDeleteCommand;

/**
 * Delete source items related to product after product deleting
 */
class DeleteSourceItemsAfterProductDeletingObserver implements ObserverInterface
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SourceItemsDeleteCommand
     */
    private $sourceItemsDeleteCommand;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * DeleteSourceItemsAfterProductDeletingObserver constructor.
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SourceItemsDeleteCommand $sourceItemsDeleteCommand
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemsDeleteCommand $sourceItemsDeleteCommand,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemsDeleteCommand = $sourceItemsDeleteCommand;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param EventObserver $observer
     * @return void
     * @throws \Exception
     */
    public function execute(EventObserver $observer)
    {
        try {
            /** @var ProductInterface $product */
            $product = $observer->getEvent()->getProduct();
            $sku = $product->getSku();
            $this->deleteSourceItemsByProductSku($sku);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param string $sku
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    private function deleteSourceItemsByProductSku($sku)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(ProductInterface::SKU, $sku)->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        if (count($sourceItems) > 0) {
            $this->sourceItemsDeleteCommand->execute($sourceItems);
        }
    }
}

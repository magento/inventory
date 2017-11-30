<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\ResourceModel\Stock;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ResourceItem;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Framework\Exception\AlreadyExistsException;

/**
 * Class provides around Plugin on Magento\CatalogInventory\Model\ResourceModel\Stock\Item::delete
 * to update data in Inventory source item
 */
class DeleteSourceItemsAtLegacyStockItemDelete
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var DeleteMultiple
     */
    private $sourceItemDeleteMultiple;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param DeleteMultiple $sourceItemDeleteMultiple
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection,
        SourceItemRepositoryInterface $sourceItemRepository,
        DeleteMultiple $sourceItemDeleteMultiple,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemDeleteMultiple = $sourceItemDeleteMultiple;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param ResourceItem $subject
     * @param callable $proceed
     * @param Item $stockItem
     *
     * @return void
     * @throws \Exception
     * @throws AlreadyExistsException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDelete(ResourceItem $subject, callable $proceed, Item $stockItem)
    {
        $connection = $this->resourceConnection->getConnection('write');
        $connection->beginTransaction();
        try {
            $proceed($stockItem);

            $product = $this->productRepository->getById($stockItem->getProductId());
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('sku', $product->getSku())
                ->create();
            $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();
            $this->sourceItemDeleteMultiple->execute($sourceItems);

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
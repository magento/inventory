<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog;

use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Inventory\Model\SourceItem\Command\SourceItemsDelete as SourceItemsDeleteCommand;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Api\Data\ProductInterface;

/**
 * Class provides around Plugin on Magento\Catalog\Model\ProductRepository::delete
 * to delete source items of deleted product
 */
class DeleteSourceItemsAfterProductDeletingPlugin
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
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SourceItemsDeleteCommand $sourceItemsDeleteCommand
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SourceItemsDeleteCommand $sourceItemsDeleteCommand,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResourceConnection $resourceConnection
    )
    {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->sourceItemsDeleteCommand = $sourceItemsDeleteCommand;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $subject
     * @param callable $proceed
     * @param ProductInterface $product
     * @return void
     * @throws \Exception
     */
    public function aroundDelete($subject, callable $proceed, ProductInterface $product)
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $sku = $product->getSku();
            $proceed($product);
            $this->deleteSourceItemsByProductSku($sku);

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

    }

    /**
     * @param string $sku
     * @return void
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

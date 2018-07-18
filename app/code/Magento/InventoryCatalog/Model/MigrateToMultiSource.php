<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class MigrateToMultiSource
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var DefaultSourceProvider
     */
    private $defaultSourceProvider;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MigrateSourceItemsToSourceInterface
     */
    private $migrateSourceItemsToSource;

    /**
     * MigrateToMultiSource constructor.
     * @param SourceItemsDeleteInterface $sourceItemsDelete
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param DefaultSourceProvider $defaultSourceProvider
     * @param ResourceConnection $resourceConnection
     * @param MigrateSourceItemsToSourceInterface $migrateSourceItemsToSource
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        DefaultSourceProvider $defaultSourceProvider,
        ResourceConnection $resourceConnection,
        MigrateSourceItemsToSourceInterface $migrateSourceItemsToSource
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->resourceConnection = $resourceConnection;
        $this->migrateSourceItemsToSource = $migrateSourceItemsToSource;
    }

    /**
     * @param array $skus
     * @param string $migrationSourceCode
     */
    public function execute(array $skus, string $migrationSourceCode)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $skus, 'in')
            ->addFilter(SourceItemInterface::SOURCE_CODE, $this->defaultSourceProvider->getCode())
            ->create();

        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        if ($sourceItems) {
            $connection = $this->resourceConnection->getConnection();
            $connection->beginTransaction();

            try {
                $this->migrateSourceItemsToSource($migrationSourceCode, $sourceItems);
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
            }
        }
    }
}

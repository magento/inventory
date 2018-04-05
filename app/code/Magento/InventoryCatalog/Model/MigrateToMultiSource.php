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
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

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

    public function __construct(
        SourceItemsDeleteInterface $sourceItemsDelete,
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        DefaultSourceProvider $defaultSourceProvider,
        ResourceConnection $resourceConnection
    ) {

        $this->sourceItemsDelete = $sourceItemsDelete;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->resourceConnection = $resourceConnection;
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
                $this->migrateSourceItems($migrationSourceCode, $sourceItems);
                $connection->commit();
            }
            catch (\Exception $e) {
                $connection->rollBack();
            }
        }
    }

    /**
     * @param string $migrationSourceCode
     * @param SourceItem[] $sourceItems
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    private function migrateSourceItems(string $migrationSourceCode, array $sourceItems)
    {
        foreach ($sourceItems as $sourceItem) {
            $sourceItem->setSourceCode($migrationSourceCode);
        }

        $this->sourceItemsDelete->execute($sourceItems);
        $this->sourceItemsSave->execute($sourceItems);
    }
}
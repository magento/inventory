<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\ResourceConnection;
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
     * @param $sku
     * @param $migrationSourceCode
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function execute($sku, $migrationSourceCode)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->addFilter(SourceItemInterface::SOURCE_CODE, $this->defaultSourceProvider->getCode())
            ->create();

        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        if ($sourceItems) {
            foreach ($sourceItems as $sourceItem) {
                $sourceItem->setSourceCode($migrationSourceCode);
            }

            $this->sourceItemsDelete->execute($sourceItems);
            $this->sourceItemsSave->execute($sourceItems);
        }
    }
}
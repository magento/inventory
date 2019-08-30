<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Model\SourceTypeLinkManagementInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\CollectionFactory;

/**
 * @inheritdoc
 */
class SourceTypeLinkManagement implements SourceTypeLinkManagementInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var CollectionFactory
     */
    private $typeLinkCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CollectionFactory $carrierLinkCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CollectionProcessorInterface $collectionProcessor,
        CollectionFactory $typeLinkCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->collectionProcessor = $collectionProcessor;
        $this->typeLinkCollectionFactory = $typeLinkCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function saveTypeLinksBySource(SourceInterface $source, $type_code): void
    {
        $this->deleteCurrentTypeLinks($source);

        $this->saveNewTypeLinks($source, $type_code);
    }

    /**
     * @param SourceInterface $source
     * @return void
     */
    private function deleteCurrentTypeLinks(SourceInterface $source)
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->delete(
            $this->resourceConnection->getTableName(SourceTypeLink::TABLE_NAME_SOURCE_TYPE_LINK),
            $connection->quoteInto('source_code = ?', $source->getSourceCode())
        );
    }

    /**
     * @param SourceInterface $source
     * @param string $type_code
     * @return void
     */
    private function saveNewTypeLinks(SourceInterface $source, $type_code)
    {
        $TypeLinkData = [
            'source_code' => $source->getSourceCode(),
            'type_code' => $type_code
        ];


        $this->resourceConnection->getConnection()->insert(
            $this->resourceConnection->getTableName(SourceTypeLink::TABLE_NAME_SOURCE_TYPE_LINK),
            $TypeLinkData
        );
    }

    /**
     * @inheritdoc
     */
    public function loadTypeLinksBySource(SourceInterface $source): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceTypeLinkManagementInterface::SOURCE_CODE, $source->getSourceCode())
            ->create();

//        /** @var Collection $collection */
        $collection = $this->typeLinkCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $extension = $source->getExtensionAttributes();
        $extension->setTypeCode($collection->getFirstItem()->getData()['type_code']);
    }
}

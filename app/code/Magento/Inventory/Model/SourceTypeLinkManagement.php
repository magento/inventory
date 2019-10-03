<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Elasticsearch\SearchAdapter\Query\Builder\Sort;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Model\SourceTypeLinkManagementInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink\CollectionFactory;

use Magento\Framework\Api\SortOrderBuilder;
use Magento\InventoryApi\Api\GetSourceTypeLinksInterface;
use Magento\InventoryApi\Api\SourceTypeLinksDeleteInterface;

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



    private $sortOrderBuilder;

    private $getSourceTypeLinks;

    private $sourceTypeLinksDelete;


    /**
     * @param ResourceConnection $resourceConnection
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CollectionFactory $typeLinkCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param GetSourceTypeLinksInterface $getSourceTypeLinks
     * @param SourceTypeLinksDeleteInterface $sourceTypeLinksDelete
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CollectionProcessorInterface $collectionProcessor,
        CollectionFactory $typeLinkCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        GetSourceTypeLinksInterface $getSourceTypeLinks,
        SourceTypeLinksDeleteInterface $sourceTypeLinksDelete
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->collectionProcessor = $collectionProcessor;
        $this->typeLinkCollectionFactory = $typeLinkCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->getSourceTypeLinks = $getSourceTypeLinks;
        $this->sourceTypeLinksDelete = $sourceTypeLinksDelete;
    }

    /**
     * @inheritdoc
     */
    public function saveTypeLinksBySource(SourceInterface $source, $type_code): void
    {
        $this->deleteCurrentTypeLinks($source);
//        $this->saveNewTypeLinks($source, $type_code);
    }

    /**
     * @param SourceInterface $source
     * @return void
     */
    private function deleteCurrentTypeLinks(SourceInterface $source)
    {
//        $connection = $this->resourceConnection->getConnection();
//        $connection->delete(
//            $this->resourceConnection->getTableName(SourceTypeLink::TABLE_NAME_SOURCE_TYPE_LINK),
//            $connection->quoteInto('source_code = ?', $source->getSourceCode())
//        );


        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceTypeLinkManagementInterface::SOURCE_CODE, $source->getSourceCode())
            ->create();

        $sourceTypes = $this->getSourceTypeLinks->execute($searchCriteria);
        $sourceTypesData = [];
        foreach ($sourceTypes->getItems() as $sourceType) {
            $sourceTypesData[] = [
                'sourceCode' => $sourceType->getSourceCode(),
                'typeCode' => $sourceType->getTypeCode()
            ];
        }

        $this->sourceTypeLinksDelete->execute($sourceTypesData);
    }

    /**
     * @param SourceInterface $source
     * @param string $type_code
     * @return void
     */
    private function saveNewTypeLinks(SourceInterface $source, $type_code)
    {
//        $TypeLinkData = [
//            'source_code' => $source->getSourceCode(),
//            'type_code' => $type_code
//        ];
//
//
//        $this->resourceConnection->getConnection()->insert(
//            $this->resourceConnection->getTableName(SourceTypeLink::TABLE_NAME_SOURCE_TYPE_LINK),
//            $TypeLinkData
//        );
    }

    /**
     * @inheritdoc
     */
    public function loadTypeLinksBySource(SourceInterface $source): SourceInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceTypeLinkManagementInterface::SOURCE_CODE, $source->getSourceCode())
            ->create();

        $sourceTypes = $this->getSourceTypeLinks->execute($searchCriteria);

        $sourceTypeFirst = $sourceTypes->getItems()[0];

        $extension = $source->getExtensionAttributes();
        $extension->setTypeCode($sourceTypeFirst->getData()['type_code']);

        $source->setExtensionAttributes($extension);

        return $source;
    }
}

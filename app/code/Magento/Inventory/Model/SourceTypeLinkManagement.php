<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\InventoryApi\Api\Data\SourceExtensionInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\InventoryApi\Model\SourceTypeLinkManagementInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\GetSourceTypeLinkInterface;
use Magento\InventoryApi\Api\SourceTypeLinkSaveInterface;
use Magento\InventoryApi\Api\SourceTypeLinkDeleteInterface;

/**
 * @inheritdoc
 */
class SourceTypeLinkManagement implements SourceTypeLinkManagementInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetSourceTypeLinkInterface
     */
    private $getSourceTypeLinks;

    /**
     * @var SourceTypeLinkDeleteInterface
     */
    private $commandDelete;

    /**
     * @var SourceTypeLinkSaveInterface
     */
    private $commandSave;

    /**
     * SourceTypeLinkManagement constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetSourceTypeLinkInterface $getSourceTypeLinks
     * @param SourceTypeLinkSaveInterface $commandSave
     * @param SourceTypeLinkDeleteInterface $commandDelete
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetSourceTypeLinkInterface $getSourceTypeLinks,
        SourceTypeLinkSaveInterface $commandSave,
        SourceTypeLinkDeleteInterface $commandDelete
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getSourceTypeLinks = $getSourceTypeLinks;
        $this->commandSave = $commandSave;
        $this->commandDelete = $commandDelete;
    }

    /**
     * @inheritdoc
     */
    public function saveTypeLinksBySource(SourceInterface $source): void
    {
        $this->deleteCurrentTypeLink($source->getSourceCode());
        $this->saveNewTypeLink($source);
    }

    /**
     * Delete current type link
     *
     * @param string $sourceCode
     */
    private function deleteCurrentTypeLink(string $sourceCode)
    {
        $this->commandDelete->execute($sourceCode);
    }

    /**
     * Save new type link
     *
     * @param SourceInterface $source
     * @return void
     */
    private function saveNewTypeLink(SourceInterface $source)
    {
        $this->commandSave->execute($source);
    }

    /**
     * @inheritdoc
     */
    public function loadTypeLinksBySource(SourceInterface $source): SourceInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceTypeLinkManagementInterface::SOURCE_CODE, $source->getSourceCode())
            ->create();

        $sourceType = $this->getSourceTypeLinks->execute($searchCriteria);

        $sourceTypeCode = SourceTypeLinkInterface::DEFAULT_SOURCE_TYPE;
        if ($sourceType->getTotalCount()) {
            /** @var SourceTypeLinkInterface $sourceTypeFirst */
            $sourceTypeFirst = current($sourceType->getItems());
            $sourceTypeCode = $sourceTypeFirst->getTypeCode();
        }

        /** @var SourceExtensionInterface $extension */
        $extension = $source->getExtensionAttributes();
        $extension->setTypeCode($sourceTypeCode);
        $source->setExtensionAttributes($extension);

        return $source;
    }
}

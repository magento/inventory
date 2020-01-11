<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\InventoryApi\Api\Data\SourceExtensionInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterfaceFactory;
use Magento\InventoryApi\Model\SourceTypeLinkManagementInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\GetSourceTypeLinkInterface;
use Magento\InventoryApi\Api\SourceTypeLinkSaveInterface;
use Magento\InventoryApi\Api\SourceTypeLinkDeleteInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;

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
     * @var SourceTypeLinkInterfaceFactory
     */
    private $sourceTypeLinkFactory;

    /**
     * SourceTypeLinkManagement constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetSourceTypeLinkInterface $getSourceTypeLinks
     * @param SourceTypeLinkSaveInterface $commandSave
     * @param SourceTypeLinkDeleteInterface $commandDelete
     * @param SourceTypeLinkInterfaceFactory $sourceTypeLinkFactory
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetSourceTypeLinkInterface $getSourceTypeLinks,
        SourceTypeLinkSaveInterface $commandSave,
        SourceTypeLinkDeleteInterface $commandDelete,
        SourceTypeLinkInterfaceFactory $sourceTypeLinkFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getSourceTypeLinks = $getSourceTypeLinks;
        $this->commandSave = $commandSave;
        $this->commandDelete = $commandDelete;
        $this->sourceTypeLinkFactory = $sourceTypeLinkFactory;
    }

    /**
     * @inheritdoc
     */
    public function saveTypeLinksBySource(SourceInterface $source): void
    {
        $linkData = [
            'source_code' => $source->getSourceCode(),
            'type_code' => $source->getExtensionAttributes()->getTypeCode()
        ];

        /** @var SourceTypeLinkInterface $link */
        $link = $this->sourceTypeLinkFactory->create();
        $link->addData($linkData);

        $this->deleteCurrentTypeLink($source->getSourceCode());
        $this->saveNewTypeLink($link);
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
     * @param SourceTypeLinkInterface $link
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     */
    private function saveNewTypeLink(SourceTypeLinkInterface $link)
    {
        $this->commandSave->execute($link);
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

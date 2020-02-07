<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterfaceFactory;
use Magento\InventoryApi\Api\SourceTypeLinkDeleteInterface;
use Magento\InventoryApi\Api\SourceTypeLinkSaveInterface;

/**
 * Plugin to save type of source after save source
 */
class AfterSourceSave implements ObserverInterface
{

    /**
     * @var SourceTypeLinkSaveInterface
     */
    private $commandSave;

    /**
     * @var SourceTypeLinkDeleteInterface
     */
    private $commandDelete;

    /**
     * @var SourceTypeLinkInterfaceFactory
     */
    private $sourceTypeLinkFactory;

    /**
     * AfterSourceSave constructor.
     *
     * @param SourceTypeLinkSaveInterface $commandSave
     * @param SourceTypeLinkDeleteInterface $commandDelete
     * @param SourceTypeLinkInterfaceFactory $sourceTypeLinkFactory
     */
    public function __construct(
        SourceTypeLinkSaveInterface $commandSave,
        SourceTypeLinkDeleteInterface $commandDelete,
        SourceTypeLinkInterfaceFactory $sourceTypeLinkFactory
    ) {
        $this->commandSave = $commandSave;
        $this->commandDelete = $commandDelete;
        $this->sourceTypeLinkFactory = $sourceTypeLinkFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var SourceInterface $source */
        $source = $observer->getEvent()->getSource();

        $this->saveTypeLinksBySource($source);
    }

    /**
     * Save Type link by source
     *
     * Get type source link from source object and save its.
     *
     * @param SourceInterface $source
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     */
    private function saveTypeLinksBySource(SourceInterface $source): void
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
}

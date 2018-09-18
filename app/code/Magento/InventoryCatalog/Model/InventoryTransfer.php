<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Inventory\Model\SourceItemTransactionManager;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\GetSourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\InventoryTransferInterface;
use Magento\InventoryCatalogApi\Api\SourceAssignInterface;
use Magento\InventoryCatalogApi\Api\SourceUnassignInterface;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InventoryTransfer implements InventoryTransferInterface
{
    /**
     * @var GetSourceItemInterface
     */
    private $getSourceItem;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemInterfaceFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceUnassignInterface
     */
    private $sourceUnassign;

    /**
     * @var SourceItemTransactionManager
     */
    private $sourceItemTransactionManager;

    /**
     * @var SourceAssignInterface
     */
    private $sourceAssign;

    /**
     * @param GetSourceItemInterface $getSourceItem
     * @param SourceItemInterfaceFactory $sourceItemInterfaceFactory
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceUnassignInterface $sourceUnassign
     * @param SourceAssignInterface $sourceAssign
     * @param SourceItemTransactionManager $sourceItemTransactionManager
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        GetSourceItemInterface $getSourceItem,
        SourceItemInterfaceFactory $sourceItemInterfaceFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        SourceUnassignInterface $sourceUnassign,
        SourceAssignInterface $sourceAssign,
        SourceItemTransactionManager $sourceItemTransactionManager
    ) {
        $this->getSourceItem = $getSourceItem;
        $this->sourceItemInterfaceFactory = $sourceItemInterfaceFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceUnassign = $sourceUnassign;
        $this->sourceItemTransactionManager = $sourceItemTransactionManager;
        $this->sourceAssign = $sourceAssign;
    }

    /**
     * @inheritdoc
     * @param string $sku
     * @param string $originSource
     * @param string $destinationSource
     * @param bool $unassignFromOrigin
     * @return bool
     * @throws \Exception
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function execute(
        string $sku,
        string $originSource,
        string $destinationSource,
        bool $unassignFromOrigin
    ): bool {
        if ($originSource === $destinationSource) {
            throw new LocalizedException(__('Origin source and destination source are the same'));
        }

        $this->sourceItemTransactionManager->begin();

        try {
            $originSourceItem = $this->getSourceItem->execute($sku, $originSource);

            // Fetch or create destination source
            try {
                $destinationSourceItem = $this->getSourceItem->execute($sku, $destinationSource);
            } catch (NoSuchEntityException $e) {
                $this->sourceAssign->execute($sku, $destinationSource);
                $destinationSourceItem = $this->getSourceItem->execute($sku, $destinationSource);
            }

            // Increase destination
            $destinationSourceItem->setQuantity(
                $destinationSourceItem->getQuantity() + $originSourceItem->getQuantity()
            );
            $destinationSourceItem->setStatus(
                $destinationSourceItem->getQuantity() > 0 ?
                    SourceItemInterface::STATUS_IN_STOCK :
                    SourceItemInterface::STATUS_OUT_OF_STOCK
            );

            if ($unassignFromOrigin) {
                $this->sourceUnassign->execute($sku, $originSource);
                $this->sourceItemsSave->execute([$destinationSourceItem]);
            } else {
                $originSourceItem->setStatus(SourceItemInterface::STATUS_OUT_OF_STOCK);
                $originSourceItem->setQuantity(0);
                $this->sourceItemsSave->execute([$originSourceItem, $destinationSourceItem]);
            }

            $this->sourceItemTransactionManager->commit();
        } catch (\Exception $e) {
            $this->sourceItemTransactionManager->rollback();
            throw $e;
        }

        return true;
    }
}

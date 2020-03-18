<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\ExpressionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\ResourceModel\Source;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Provides list of Source Codes which have all requested products assigned.
 */
class GetPickupLocationIntersectionForSkus
{
    /**
     * @var SourceItem
     */
    private $sourceItemResource;

    /**
     * @var Source
     */
    private $sourceResource;

    /**
     * @var ExpressionFactory
     */
    private $expressionFactory;

    /**
     * @param SourceItem $sourceItemResource
     * @param Source $sourceResource
     * @param ExpressionFactory $expressionFactory
     */
    public function __construct(
        SourceItem $sourceItemResource,
        Source $sourceResource,
        ExpressionFactory $expressionFactory
    ) {
        $this->sourceItemResource = $sourceItemResource;
        $this->sourceResource = $sourceResource;
        $this->expressionFactory = $expressionFactory;
    }

    /**
     * Provide intersection of products availability in sources.
     *
     * @param string[] $skus
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $skus): array
    {
        $select = $this->sourceItemResource->getConnection()->select();
        $expression = $this->expressionFactory->create(['expression' => 'COUNT(' . SourceItemInterface::SKU . ')']);
        $select->from($this->sourceItemResource->getMainTable())
            ->joinInner(
                $this->sourceResource->getMainTable(),
                $this->sourceItemResource->getMainTable() . '.' . SourceItemInterface::SOURCE_CODE . '=' .
                $this->sourceResource->getMainTable() . '.' . SourceInterface::SOURCE_CODE
            )
            ->where(SourceItemInterface::SKU . ' in (?) ', $skus)
            ->where(SourceInterface::ENABLED . ' = 1')
            ->where(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE . ' = 1')
            ->reset(Select::COLUMNS)
            ->columns([SourceItemInterface::SOURCE_CODE])
            ->group($this->sourceItemResource->getMainTable() . '.' . SourceItemInterface::SOURCE_CODE)
            ->having($expression . ' = ' . count($skus));
        $result = $this->sourceItemResource->getConnection()->fetchAssoc($select);
        $sourceCodes = [];
        foreach ($result as $row) {
            $sourceCodes[] = $row[SourceItemInterface::SOURCE_CODE];
        }

        return $sourceCodes;
    }
}

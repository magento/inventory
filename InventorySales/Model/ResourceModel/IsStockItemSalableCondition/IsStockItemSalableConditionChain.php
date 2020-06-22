<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;

/**
 * Chain of stock item salable conditions.
 */
class IsStockItemSalableConditionChain implements GetIsStockItemSalableConditionInterface
{
    /**
     * @var GetIsStockItemSalableConditionInterface[]
     */
    private $conditions = [];

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StockConfigurationInterface
     */
    private $configuration;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StockConfigurationInterface $configuration
     * @param array $conditions
     * @throws LocalizedException
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StockConfigurationInterface $configuration,
        array $conditions = []
    ) {
        foreach ($conditions as $getIsSalableCondition) {
            if (!$getIsSalableCondition instanceof GetIsStockItemSalableConditionInterface) {
                throw new LocalizedException(
                    __('Condition must implement %1', GetIsStockItemSalableConditionInterface::class)
                );
            }
        }
        $this->resourceConnection = $resourceConnection;
        $this->conditions = $conditions;
        $this->configuration = $configuration;
    }

    /**
     * @inheritdoc
     */
    public function execute(Select $select): string
    {
        if (empty($this->conditions) || !$this->configuration->getManageStock()) {
            return '1';
        }

        $conditionStrings = [];
        foreach ($this->conditions as $condition) {
            $conditionString = $condition->execute($select);
            if ('' !== trim($conditionString)) {
                $conditionStrings[] = $conditionString;
            }
        }

        $isSalableString = '(' . implode(') OR (', $conditionStrings) . ')';
        return (string)$this->resourceConnection->getConnection()->getCheckSql($isSalableString, 1, 0);
    }
}

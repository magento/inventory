<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForSalesChannelCondition;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForSalesChannelInterface;

/**
 * @inheritdoc
 */
class IsProductSalableForSalesChannelConditionChain implements IsProductSalableForSalesChannelInterface
{
    /**
     * @var IsProductSalableForSalesChannelInterface[]
     */
    private $conditions;

    /**
     * @param array $conditions
     * @throws LocalizedException
     */
    public function __construct(
        array $conditions
    ) {
        $this->setConditions($conditions);
    }

    /**
     * @param array $conditions
     * @throws LocalizedException
     */
    private function setConditions(array $conditions)
    {
        $this->validateConditions($conditions);
        $conditions = $this->sortConditions($conditions);
        // TODO just assign conditions, postpone sorting on fist execute call - no logic in constructors
        $this->conditions = array_column($conditions, 'object');
    }

    /**
     * @param array $conditions
     * @throws LocalizedException
     */
    private function validateConditions(array $conditions)
    {
        foreach ($conditions as $condition) {
            if (empty($condition['object'])) {
                throw new LocalizedException(__('Parameter "object" must be present.'));
            }

            if (empty($condition['sort_order'])) {
                throw new LocalizedException(__('Parameter "sort_order" must be present.'));
            }

            if (!$condition['object'] instanceof IsProductSalableForSalesChannelInterface) {
                throw new LocalizedException(
                    __('Condition have to implement IsProductSalableForSalesChannelInterface.')
                );
            }
        }
    }

    /**
     * @param array $conditions
     * @return array
     */
    private function sortConditions(array $conditions)
    {
        usort($conditions, function (array $conditionLeft, array $conditionRight) {
            if ($conditionLeft['sort_order'] == $conditionRight['sort_order']) {
                return 0;
            }
            return ($conditionLeft['sort_order'] < $conditionRight['sort_order']) ? -1 : 1;
        });
        return $conditions;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, SalesChannelInterface $salesChannel): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->execute($sku, $salesChannel) === true) {
                return true;
            }
        }

        return false;
    }
}

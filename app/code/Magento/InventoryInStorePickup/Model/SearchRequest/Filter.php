<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;

/**
 * @inheritdoc
 */
class Filter implements FilterInterface
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $conditionType;

    /**
     * @param string $value
     * @param string $conditionType
     */
    public function __construct(
        string $value,
        string $conditionType = 'eq'
    ) {
        $this->value = $value;
        $this->conditionType = $conditionType;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @inheritdoc
     */
    public function getConditionType(): string
    {
        return $this->conditionType;
    }
}

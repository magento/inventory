<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Builder;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterfaceFactory;

/**
 * Filter Builder.
 */
class FilterBuilder
{
    private const FIELD_VALUE = 'value';
    private const FIELD_CONDITION_TYPE = 'conditionType';

    /**
     * Filter data.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var FilterInterfaceFactory
     */
    private $filterFactory;

    /**
     * @param FilterInterfaceFactory $filterFactory
     */
    public function __construct(FilterInterfaceFactory $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    /**
     * Create Filter object.
     *
     * @return FilterInterface
     */
    public function create(): ?FilterInterface
    {
        $data = $this->data;
        $this->data = [];

        if (array_key_exists(self::FIELD_CONDITION_TYPE, $data) && $data[self::FIELD_CONDITION_TYPE] === null) {
            unset($data[self::FIELD_CONDITION_TYPE]);
        }

        return empty($data) ? null : $this->filterFactory->create($data);
    }

    /**
     * @inheritdoc
     */
    public function setValue(string $value): self
    {
        $this->data[self::FIELD_VALUE] = $value;

        return $this;
    }

    /**
     * Set Filter Condition Type.
     *
     * @param string|null $conditionType
     *
     * @return FilterBuilder
     */
    public function setConditionType(?string $conditionType): self
    {
        $this->data[self::FIELD_CONDITION_TYPE] = $conditionType;

        return $this;
    }
}

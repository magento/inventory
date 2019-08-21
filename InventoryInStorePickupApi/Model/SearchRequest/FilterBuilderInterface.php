<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchRequest;

use Magento\Framework\Api\SimpleBuilderInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;

/**
 * Filter Builder.
 *
 * @api
 */
interface FilterBuilderInterface extends SimpleBuilderInterface
{
    /**
     * Create Filter object.
     *
     * @return FilterInterface|null
     */
    public function create(): ?FilterInterface;

    /**
     * Set Value for the Filter.
     *
     * @param string $value
     *
     * @return FilterBuilderInterface
     */
    public function setValue(string $value): self;

    /**
     * Set Condition Type for the Filter.
     *
     * @param string|null $conditionType
     *
     * @return FilterBuilderInterface
     */
    public function setConditionType(?string $conditionType): self;
}

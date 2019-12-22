<?php
/**
 *  Copyright © Magento, Inc. All rights reserved.
 *  See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\PickupLocation\Mapper\PreProcessor;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\PickupLocation\Mapper\PreProcessor\FrontendDescription\Filter;
use Magento\InventoryInStorePickupApi\Model\Mapper\PreProcessorInterface;

/**
 * Processor for transferring Frontend Description from Source entity to Pickup Location entity Description.
 */
class FrontendDescription implements PreProcessorInterface
{
    /**
     * @var Filter
     */
    private $descriptionFilter;

    /**
     * @param Filter $descriptionFilter
     */
    public function __construct(Filter $descriptionFilter)
    {
        $this->descriptionFilter = $descriptionFilter;
    }

    /**
     * Process Source Field before pass it to Pickup Location
     *
     * @param SourceInterface $source
     * @param string|null $value Source Field Value
     * @return string|null
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process(SourceInterface $source, $value): ?string
    {
        return $value ? $this->descriptionFilter->filter($value) : null;
    }
}

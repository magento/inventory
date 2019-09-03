<?php
/**
 *  Copyright © Magento, Inc. All rights reserved.
 *  See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\PickupLocation\Mapper\PreProcessor;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper\PreProcessorInterface;
use Magento\Widget\Model\Template\FilterEmulate;

/**
 * Processor for transferring Frontend Description from Source entity to Pickup Location entity Description.
 */
class FrontendDescription implements PreProcessorInterface
{
    /**
     * @var FilterEmulate
     */
    private $filterEmulate;

    /**
     * @param FilterEmulate $filterEmulate
     */
    public function __construct(FilterEmulate $filterEmulate)
    {
        $this->filterEmulate = $filterEmulate;
    }

    /**
     * @inheritdoc
     */
    public function process(SourceInterface $source, $value): string
    {
        return $this->filterEmulate->filter($value);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use InvalidArgumentException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper\CreateFromSourceInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper\PreProcessorInterface;

/**
 * Create projection of sources on In-Store Pickup context.
 * Data transfer from source to projection will be done according to provided fields mapping.
 *
 * @api
 */
class Mapper
{
    /**
     * Attributes map for projection.
     *
     * @var array
     */
    private $map;

    /**
     * @var CreateFromSourceInterface
     */
    private $createFromSource;

    /**
     * @var PreProcessorInterface[]
     */
    private $preProcessors;

    /**
     * @param CreateFromSourceInterface $createFromSource
     * @param string[] $map
     * @param PreProcessorInterface[] $preProcessors
     * @throws InvalidArgumentException
     */
    public function __construct(
        CreateFromSourceInterface $createFromSource,
        array $map = [],
        array $preProcessors = []
    ) {
        $this->createFromSource = $createFromSource;
        $this->map = $map;

        foreach ($preProcessors as $preProcessor) {
            if (!$preProcessor instanceof PreProcessorInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Source Data PreProcessor must implement %s.',
                        PreProcessorInterface::class
                    )
                );
            }
        }
        $this->preProcessors = $preProcessors;
    }

    /**
     * @param SourceInterface $source
     *
     * @return PickupLocationInterface
     */
    public function map(SourceInterface $source): PickupLocationInterface
    {
        return $this->createFromSource->execute($source, $this->map, $this->preProcessors);
    }
}

<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for \Magento\InventoryMultiDimensionalIndexerApi\Model\Dimension
 *
 * @api
 */
class DimensionFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create an instance of Dimension
     *
     * @param array $arguments
     * @return Dimension
     */
    public function create(array $arguments = []): Dimension
    {
        return $this->objectManager->create(Dimension::class, $arguments);
    }
}

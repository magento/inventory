<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for \Magento\InventoryMultiDimensionalIndexerApi\Model\Alias
 *
 * @api
 */
class AliasFactory
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
     * Creates an `Alias` object using the provided arguments and returns the instance.
     *
     * @param array $arguments
     * @return Alias
     */
    public function create(array $arguments = []): Alias
    {
        return $this->objectManager->create(Alias::class, $arguments);
    }
}

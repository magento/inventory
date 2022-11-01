<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

use Magento\Framework\Exception\InvalidArgumentException;

class CompositeProductStockStatusProcessorPool implements CompositeProductStockStatusProcessorInterface
{
    /**
     * @var CompositeProductStockStatusProcessorInterface[]
     */
    private array $compositeProductStockStatusProcessors;

    /**
     * Initializes dependencies
     *
     * @param CompositeProductStockStatusProcessorInterface[] $compositeProductStockStatusProcessors
     * @throws InvalidArgumentException
     */
    public function __construct(
        array $compositeProductStockStatusProcessors
    ) {
        foreach ($compositeProductStockStatusProcessors as $compositeProductStockStatusProcessor) {
            if (!$compositeProductStockStatusProcessor instanceof CompositeProductStockStatusProcessorInterface) {
                throw new InvalidArgumentException(
                    __(
                        '%1 must implement %2.',
                        get_class($compositeProductStockStatusProcessor),
                        CompositeProductStockStatusProcessorInterface::class
                    )
                );
            }
        }
        $this->compositeProductStockStatusProcessors = $compositeProductStockStatusProcessors;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): void
    {
        foreach ($this->compositeProductStockStatusProcessors as $compositeProductStockStatusProcessor) {
            $compositeProductStockStatusProcessor->execute($skus);
        }
    }
}

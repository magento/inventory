<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

class CompositeProductSalabilityChangeProcessor implements ProductSalabilityChangeProcessorInterface
{
    /**
     * @param ProductSalabilityChangeProcessorInterface[] $processors
     */
    public function __construct(private readonly array $processors)
    {
        foreach ($processors as $processor) {
            if (!$processor instanceof ProductSalabilityChangeProcessorInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Product salability change processor %s must implement %s',
                        get_class($processor),
                        ProductSalabilityChangeProcessorInterface::class
                    )
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): void
    {
        foreach ($this->processors as $processor) {
            $processor->execute($skus);
        }
    }
}

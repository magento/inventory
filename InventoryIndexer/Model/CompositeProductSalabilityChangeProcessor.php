<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
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

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use InvalidArgumentException;

/**
 * Class OptionStockResolver
 */
class OptionStockHandlerPool
{
    /**
     * @var array
     */
    private $optionStockHandlers;

    /**
     * OptionStockResolver constructor
     *
     * @param array $optionStockHandlers
     */
    public function __construct(
        array $optionStockHandlers
    ) {
        $this->optionStockHandlers = $optionStockHandlers;
        foreach ($this->optionStockHandlers as $optionStockHandler) {
            if (!$optionStockHandler instanceof OptionStockHandlerInterface) {
                throw new InvalidArgumentException(
                    get_class($optionStockHandler) . ' object doesn\'t implements Magento\InventoryBundleProductIndexer\Indexer\OptionStockHandlerInterface'
                );
            }
        }
    }

    /**
     * Provides optionStockHandlerByType
     *
     * @param string $optionType
     *
     * @return OptionStockHandlerInterface
     */
    public function get(string $optionType): OptionStockHandlerInterface
    {
        return $this->optionStockHandlers[$optionType];
    }
}

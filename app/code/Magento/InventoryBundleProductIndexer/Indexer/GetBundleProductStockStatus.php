<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Bundle\Model\OptionRepository;

/**
 * Class GetBundleProductStockStatus
 */
class GetBundleProductStockStatus
{
    /**
     * @var OptionRepository
     */
    private $optionRepository;

    /**
     * @var OptionStockHandlerPool
     */
    private $optionStockHandlerPool;

    /**
     * GetBundleProductStockStatus constructor
     *
     * @param OptionRepository $optionRepository
     * @param OptionStockHandlerPool $optionStockHandlerPool
     */
    public function __construct(
        OptionRepository $optionRepository,
        OptionStockHandlerPool $optionStockHandlerPool
    ) {
        $this->optionRepository = $optionRepository;
        $this->optionStockHandlerPool = $optionStockHandlerPool;
    }

    /**
     * Provides bundle product stock status
     *
     * @param OptionInterface[] $bundleOptions
     * @param array $stock
     *
     * @return bool
     */
    public function execute(array $bundleOptions, array $stock): bool
    {
        $bundleOptionsStockStatus = 0;
        foreach ($bundleOptions as $option) {
            $optionStockHandler = $this->optionStockHandlerPool->get($option->getType());
            $isOptionInStock = $optionStockHandler->isOptionInStock($option, $stock);
            if ($option->getRequired() && !$isOptionInStock) {
                return false;
            }
            $bundleOptionsStockStatus += (int)$isOptionInStock;
        }
        if ($bundleOptionsStockStatus > 0) {
            return true;
        }

        return false;
    }
}

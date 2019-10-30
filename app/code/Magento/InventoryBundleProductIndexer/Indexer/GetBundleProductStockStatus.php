<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer;

use Magento\Bundle\Model\OptionRepository;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @param array $bundleOptions
     * @param array $stock
     *
     * @return bool
     */
    public function execute(array $bundleOptions, array $stock): bool
    {
        foreach ($bundleOptions as $option) {
            if ((int)$option['is_required'] === 0) {
                continue;
            }
            $optionStockHandler = $this->optionStockHandlerPool->get($option['type']);
            if (!$optionStockHandler->isOptionInStock($option, $stock)) {
                return false;
            }
        }

        return true;
    }
}

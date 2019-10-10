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
     * @param string $sku
     * @param array $stock
     *
     * @return bool
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function execute(string $sku, array $stock): bool
    {
        $optionList = $this->optionRepository->getList($sku);
        foreach ($optionList as $option) {
            if ((int)$option->getRequired() === 0) {
                continue;
            }
            $optionStockHandler = $this->optionStockHandlerPool->get($option->getType());
            if (!$optionStockHandler->isOptionInStock($option, $stock)) {
                return false;
            }
        }

        return true;
    }
}

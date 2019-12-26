<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\Stock;

use Magento\InventoryBundleProduct\Model\GetBundleProductStockStatus;

/**
 * Returns all data for the index by stock id condition
 */
class IndexDataByStockIdProvider
{
    /**
     * @var GetAllBundleProductsService
     */
    private $getAllBundleProductsService;

    /**
     * @var GetBundleProductStockStatus
     */
    private $getBundleProductStockStatus;

    /**
     * @var GetSimpleProductStockByBundleSkus
     */
    private $getSimpleProductStockByBundleSkus;

    /**
     * @var GetBundleOptionsByBundleSkus
     */
    private $getBundleOptionsByBundleSkus;

    /**
     * @param GetAllBundleProductsService $getAllBundleProductsService
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     * @param GetSimpleProductStockByBundleSkus $getSimpleProductStockByBundleSkus
     * @param GetBundleOptionsByBundleSkus $getBundleOptionsByBundleSkus
     */
    public function __construct(
        GetAllBundleProductsService $getAllBundleProductsService,
        GetBundleProductStockStatus $getBundleProductStockStatus,
        GetSimpleProductStockByBundleSkus $getSimpleProductStockByBundleSkus,
        GetBundleOptionsByBundleSkus $getBundleOptionsByBundleSkus
    ) {
        $this->getAllBundleProductsService = $getAllBundleProductsService;
        $this->getBundleProductStockStatus = $getBundleProductStockStatus;
        $this->getSimpleProductStockByBundleSkus = $getSimpleProductStockByBundleSkus;
        $this->getBundleOptionsByBundleSkus = $getBundleOptionsByBundleSkus;
    }

    /**
     * Index Stock provider
     *
     * @param int $stockId
     *
     * @return \ArrayIterator
     * @throws \Exception
     */
    public function execute(int $stockId): \ArrayIterator
    {
        $bundleProductCollection = $this->getAllBundleProductsService->execute();
        $inventory = [];
        $pages = $bundleProductCollection->getLastPageNumber();

        for ($i = 1; $i <= $pages; $i++) {
            $bundleProductCollection->setCurPage($i);
            $bundleProductCollection->load();
            $bundleOptionsData = $this->getBundleOptionsByBundleSkus->execute($bundleProductCollection);
            foreach ($bundleProductCollection as $bundleProduct) {
                $inventory[] = [
                    'sku' => $bundleProduct->getSku(),
                    'quantity' => 0,
                    'is_salable' => (int)$this->getBundleProductStockStatus->execute(
                        $bundleProduct,
                        $bundleOptionsData[$bundleProduct->getSku()],
                        $stockId
                    ),
                ];
            }
            $bundleProductCollection->clear();
        }

        return new \ArrayIterator($inventory);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\Stock;

use ArrayIterator;
use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryBundleProductIndexer\Indexer\GetBundleProductStockStatus;

/**
 * Returns all data for the index by stock id condition
 */
class IndexDataByStockIdProvider
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

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
     * @param ResourceConnection $resourceConnection
     * @param GetAllBundleProductsService $getAllBundleProductsService
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     * @param GetSimpleProductStockByBundleSkus $getSimpleProductStockByBundleSkus
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetAllBundleProductsService $getAllBundleProductsService,
        GetBundleProductStockStatus $getBundleProductStockStatus,
        GetSimpleProductStockByBundleSkus $getSimpleProductStockByBundleSkus
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getAllBundleProductsService = $getAllBundleProductsService;
        $this->getBundleProductStockStatus = $getBundleProductStockStatus;
        $this->getSimpleProductStockByBundleSkus = $getSimpleProductStockByBundleSkus;
    }

    /**
     * Index Stock provider
     *
     * @param int $stockId
     *
     * @return ArrayIterator
     * @throws Exception
     */
    public function execute(int $stockId): ArrayIterator
    {
        $bundleProductCollection = $this->getAllBundleProductsService->execute();
        $inventory = [];
        $pages = $bundleProductCollection->getLastPageNumber();

        for ($i = 1; $i <= $pages; $i++) {
            $bundleProductCollection->setCurPage($i);
            $bundleProductCollection->load();

            $stockData = $this->getSimpleProductStockByBundleSkus->execute($bundleProductCollection, $stockId);
            foreach ($bundleProductCollection as $bundleProduct) {
                $inventory[] = [
                    'sku' => $bundleProduct->getSku(),
                    'quantity' => 0,
                    'is_salable' => (int) $this->getBundleProductStockStatus->execute($bundleProduct->getSku(), $stockData)
                ];
            }
            $bundleProductCollection->clear();
        }

        return new ArrayIterator($inventory);
    }
}

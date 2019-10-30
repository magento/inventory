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
     * @var GetBundleOptionsByBundleSkus
     */
    private $getBundleOptionsByBundleSkus;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetAllBundleProductsService $getAllBundleProductsService
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     * @param GetSimpleProductStockByBundleSkus $getSimpleProductStockByBundleSkus
     * @param GetBundleOptionsByBundleSkus $getBundleOptionsByBundleSkus
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetAllBundleProductsService $getAllBundleProductsService,
        GetBundleProductStockStatus $getBundleProductStockStatus,
        GetSimpleProductStockByBundleSkus $getSimpleProductStockByBundleSkus,
        GetBundleOptionsByBundleSkus $getBundleOptionsByBundleSkus
    ) {
        $this->resourceConnection = $resourceConnection;
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
            $bundleOptionsData =$this->getBundleOptionsByBundleSkus->execute($bundleProductCollection);
            foreach ($bundleProductCollection as $bundleProduct) {
                $inventory[] = [
                    'sku' => $bundleProduct->getSku(),
                    'quantity' => 0,
                    'is_salable' => (int) $this->getBundleProductStockStatus
                        ->execute($bundleOptionsData[$bundleProduct->getSku()], $stockData)
                ];
            }
            $bundleProductCollection->clear();
        }

        return new ArrayIterator($inventory);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Export;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem\Collection;
use Magento\Inventory\Model\ResourceModel\StockSourceLink;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Class responsible for filtering Source Items Collection by Website
 */
class SourceItemCollectionWebsiteFilter
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @param ResourceConnection $resourceConnection
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->websiteRepository = $websiteRepository;
    }

    /**
     * Filters Source Item collection by provided website codes
     *
     * @param Collection $collection
     * @param array $websiteCodes
     * @return Collection
     */
    public function filterByWebsiteCodes(Collection $collection, array $websiteCodes): Collection
    {
        $select = $collection->getSelect();
        $select->joinLeft(
            ['source_stock_link' => $this->resourceConnection
                ->getTableName(StockSourceLink::TABLE_NAME_STOCK_SOURCE_LINK)],
            'main_table.source_code = source_stock_link.source_code',
            ['stock_id']
        )->joinLeft(
            ['sales_channel'=> $this->resourceConnection->getTableName('inventory_stock_sales_channel')],
            'sales_channel.stock_id = source_stock_link.stock_id',
            ['code']
        )->where('sales_channel.code IN (?)', $websiteCodes);

        return $collection;
    }
}

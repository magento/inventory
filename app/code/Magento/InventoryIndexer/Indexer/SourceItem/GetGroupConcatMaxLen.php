<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory;

/**
 * Returns relations between stock and sku list
 */
class GetGroupConcatMaxLen
{
    const MYSQL_GROUP_CONCAT_MAX_LEN = 1024;

    /**
     * @var CollectionFactory
     */
    private $sourceItemCollectionFactory;

    /**
     * GetSkuListInStock constructor.
     *
     * @param CollectionFactory     $sourceItemCollectionFactory
     */
    public function __construct(
        CollectionFactory $sourceItemCollectionFactory
    ) {
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
    }

    public function execute():int
    {
        $groupConcatMaxLen = self::MYSQL_GROUP_CONCAT_MAX_LEN;
        $collection = $this->sourceItemCollectionFactory->create();
        if($collection->getSize() >= self::MYSQL_GROUP_CONCAT_MAX_LEN){
            $groupConcatMaxLen = (int) $collection->getSize();
        }
        return $groupConcatMaxLen;
    }
}
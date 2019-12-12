<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryCatalogApi\Model\DeleteSourceItemsBySkusInterface;

/**
 * @inheritDoc
 */
class DeleteSourceItemsBySkus implements DeleteSourceItemsBySkusInterface
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SourceItemsDeleteInterface $sourceItemsDelete
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SourceItemsDeleteInterface $sourceItemsDelete
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemsDelete = $sourceItemsDelete;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skus): void
    {
        foreach ($skus as $sku) {
            $sourceItems = $this->getSourceItemsBySku->execute($sku);
            if ($sourceItems) {
                $this->sourceItemsDelete->execute($sourceItems);
            }
        }
    }
}

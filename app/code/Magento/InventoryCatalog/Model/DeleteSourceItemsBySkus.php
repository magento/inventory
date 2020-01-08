<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Api\Data\ResultInterface;
use Magento\InventoryCatalogApi\Api\Data\ResultInterfaceFactory;
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
     * @var DeleteMultiple
     */
    private $sourceItemsDelete;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultInterfaceFactory;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param DeleteMultiple $sourceItemsDelete
     * @param ResultInterfaceFactory $resultInterfaceFactory
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        DeleteMultiple $sourceItemsDelete,
        ResultInterfaceFactory $resultInterfaceFactory
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemsDelete = $sourceItemsDelete;
        $this->resultInterfaceFactory = $resultInterfaceFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skus): ResultInterface
    {
        $failed = [];
        foreach ($skus as $sku) {
            $sourceItems = $this->getSourceItemsBySku->execute($sku);
            if ($sourceItems) {
                try {
                    $this->sourceItemsDelete->execute($sourceItems);
                } catch (\Exception $e) {
                    $failed[] = [
                        'sku' => $sku,
                        'message' => __('Not able to delete source items.'),
                    ];
                    continue;
                }
            }
        }

        return $this->resultInterfaceFactory->create(['failed' => $failed]);
    }
}

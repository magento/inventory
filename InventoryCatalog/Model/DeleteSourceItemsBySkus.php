<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Psr\Log\LoggerInterface;

/**
 * Delete source items for given products service.
 */
class DeleteSourceItemsBySkus
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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param DeleteMultiple $sourceItemsDelete
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        DeleteMultiple $sourceItemsDelete,
        LoggerInterface $logger
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemsDelete = $sourceItemsDelete;
        $this->logger = $logger;
    }

    /**
     * Delete source items for given products skus.
     *
     * @param string[] $skus
     * @return void
     */
    public function execute(array $skus): void
    {
        foreach ($skus as $sku) {
            $sourceItems = $this->getSourceItemsBySku->execute($sku);
            if ($sourceItems) {
                try {
                    $this->sourceItemsDelete->execute($sourceItems);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                    continue;
                }
            }
        }
    }
}

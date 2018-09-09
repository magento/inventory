<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryCatalogApi\Api\SourceUnassignInterface;

/**
 * @inheritdoc
 */
class SourceUnassign implements SourceUnassignInterface
{
    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemInterfaceFactory;

    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @param SourceItemInterfaceFactory $sourceItemInterfaceFactory
     * @param SourceItemsDeleteInterface $sourceItemsDelete
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        SourceItemInterfaceFactory $sourceItemInterfaceFactory,
        SourceItemsDeleteInterface $sourceItemsDelete
    ) {
        $this->sourceItemInterfaceFactory = $sourceItemInterfaceFactory;
        $this->sourceItemsDelete = $sourceItemsDelete;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, string $sourceCode): bool
    {
        $sourceItem = $this->sourceItemInterfaceFactory->create();
        $sourceItem->setSku($sku);
        $sourceItem->setSourceCode($sourceCode);

        $this->sourceItemsDelete->execute([$sourceItem]);

        return true;
    }
}

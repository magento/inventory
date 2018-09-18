<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\SourceAssignInterface;

/**
 * @inheritdoc
 */
class SourceAssign implements SourceAssignInterface
{
    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemInterfaceFactory;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @param SourceItemInterfaceFactory $sourceItemInterfaceFactory
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        SourceItemInterfaceFactory $sourceItemInterfaceFactory,
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        $this->sourceItemInterfaceFactory = $sourceItemInterfaceFactory;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, string $sourceCode): bool
    {
        $sourceItem = $this->sourceItemInterfaceFactory->create();
        $sourceItem->setSku($sku);
        $sourceItem->setQuantity(0);
        $sourceItem->setSourceCode($sourceCode);
        $sourceItem->setStatus(SourceItemInterface::STATUS_OUT_OF_STOCK);

        $this->sourceItemsSave->execute([$sourceItem]);

        return true;
    }
}

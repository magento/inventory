<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\Product;

use Magento\Catalog\Model\Product;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalog\Model\DeleteSourceItemsBySkus;
use Magento\InventoryCatalog\Model\ResourceModel\UpdateSourceItemsSku;

/**
 * Process source items after product save.
 */
class ProcessSourceItemsPlugin
{
    /**
     * @var DeleteSourceItemsBySkus
     */
    private $deleteSourceItemsBySkus;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var UpdateSourceItemsSku
     */
    private $updateSourceItemsSkus;

    /**
     * @param DeleteSourceItemsBySkus $deleteSourceItemsBySkus
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param UpdateSourceItemsSku $updateSourceItemsSkus
     */
    public function __construct(
        DeleteSourceItemsBySkus $deleteSourceItemsBySkus,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        UpdateSourceItemsSku $updateSourceItemsSkus
    ) {
        $this->deleteSourceItemsBySkus = $deleteSourceItemsBySkus;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->updateSourceItemsSkus = $updateSourceItemsSkus;
    }

    /**
     * Update source items in case product sku has changed or delete ones in case product type has been changed.
     *
     * @param Product $subject
     * @return void
     */
    public function beforeAfterSave(Product $subject): void
    {
        $origSku = $subject->getOrigData('sku');
        $origType = $subject->getOrigData('type_id');

        if ($origType !== null && $origType !== $subject->getTypeId()) {
            $this->deleteSourceItemsBySkus->execute([(string)$subject->getOrigData('sku')]);
        }

        if ($origSku !== null && $origSku !== $subject->getSku()) {
            $this->updateSourceItemsSkus->execute($origSku, $subject->getSku());
        }

        return;
    }
}

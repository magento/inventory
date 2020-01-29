<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\InventoryCatalog\Model\DeleteSourceItemsBySkus;

/**
 * Remove source items after given product has been deleted plugin.
 */
class DeleteSourceItemsPlugin
{
    /**
     * @var DeleteSourceItemsBySkus
     */
    private $deleteSourceItemsBySkus;

    /**
     * @param DeleteSourceItemsBySkus $deleteSourceItemsBySkus
     */
    public function __construct(
        DeleteSourceItemsBySkus $deleteSourceItemsBySkus
    ) {
        $this->deleteSourceItemsBySkus = $deleteSourceItemsBySkus;
    }

    /**
     * Delete source items after product has been deleted.
     *
     * @param Product $subject
     * @param Product $result
     * @param ProductInterface $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterDelete(Product $subject, $result, $product): Product
    {
        $this->deleteSourceItemsBySkus->execute([(string)$product->getSku()]);

        return $result;
    }
}

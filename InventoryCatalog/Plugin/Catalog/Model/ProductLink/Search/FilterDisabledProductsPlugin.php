<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ProductLink\Search;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductLink\Search;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Remove disabled product from search collection plugin.
 */
class FilterDisabledProductsPlugin
{
    /**
     * Filter disabled products.
     *
     * @param Search $subject
     * @param Collection $collection
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPrepareCollection(Search $subject, Collection $collection): Collection
    {
        $collection->addAttributeToFilter(ProductInterface::STATUS, ['eq' => Status::STATUS_ENABLED]);

        return $collection;
    }
}

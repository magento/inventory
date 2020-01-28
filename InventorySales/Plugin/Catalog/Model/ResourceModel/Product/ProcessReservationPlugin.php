<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Model\AbstractModel;
use Magento\InventorySales\Model\ResourceModel\UpdateReservationsBySku;

/**
 * Process reservations after product save plugin.
 */
class ProcessReservationPlugin
{
    /**
     * @var UpdateReservationsBySku
     */
    private $updateReservationsBySku;

    /**
     * @param UpdateReservationsBySku $updateReservationsBySku
     */
    public function __construct(UpdateReservationsBySku $updateReservationsBySku)
    {
        $this->updateReservationsBySku = $updateReservationsBySku;
    }

    /**
     * Update reservations in case product sku has been changed.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     * @return Product
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        Product $subject,
        Product $result,
        AbstractModel $product
    ): Product {
        $origSku = $product->getOrigData('sku');
        if ($origSku !== null && $origSku !== $product->getSku()) {
            $this->updateReservationsBySku->execute($origSku, $product->getSku());
        }

        return $result;
    }
}

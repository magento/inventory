<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;

/**
 * @inheritdoc
 */
class GetProductBackorderWarnings implements GetProductBackorderWarningsInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var ProductSalabilityErrorInterfaceFactory
     */
    private $productSalabilityErrorFactory;

    /**
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param GetStockItemDataInterface $getStockItemData
     */
    public function __construct(
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        GetStockItemDataInterface $getStockItemData
    ) {
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId, float $requestedQty): array
    {
        $warnings = [];
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        if ($stockItemData['quantity'] < $requestedQty) {
            $warnings[] =
                $this->productSalabilityErrorFactory->create([
                    'code' => 'back_order-notify_qty',
                    'message' => __(
                        'We don\'t have as many as you requested, but we\'ll back order the remaining %1.',
                        $requestedQty - $stockItemData['quantity']
                    )
                ]);
        }

        return $warnings;
    }
}

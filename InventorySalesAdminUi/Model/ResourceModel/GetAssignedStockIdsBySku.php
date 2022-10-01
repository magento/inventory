<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model\ResourceModel;

use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventorySalesAdminUi\Model\GetStockSourceLinksBySourceCode;

class GetAssignedStockIdsBySku
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var GetStockSourceLinksBySourceCode
     */
    private $getStockSourceLinksBySourceCode;

    /**
     * @var GetStockIdsBySourceCodes
     */
    private $getStockIdsBySourceCodes;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param GetStockSourceLinksBySourceCode $getStockSourceLinksBySourceCode
     * @param GetStockIdsBySourceCodes $getStockIdsBySourceCodes
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        GetStockSourceLinksBySourceCode $getStockSourceLinksBySourceCode,
        GetStockIdsBySourceCodes $getStockIdsBySourceCodes
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->getStockSourceLinksBySourceCode = $getStockSourceLinksBySourceCode;
        $this->getStockIdsBySourceCodes = $getStockIdsBySourceCodes;
    }

    /**
     * Get all stocks Ids by sku
     *
     * @param string $sku
     * @return array
     */
    public function execute(string $sku): array
    {
        $sourceItems = $this->getSourceItemsBySku->execute($sku);
        $sourceCodes = [];
        foreach ($sourceItems as $sourceItem) {
            $sourceCodes[] = $sourceItem->getSourceCode();
        }
        $stocksIds = $this->getStockIdsBySourceCodes->execute($sourceCodes);

        return $stocksIds;
    }
}

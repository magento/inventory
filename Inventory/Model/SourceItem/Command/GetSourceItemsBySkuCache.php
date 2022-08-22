<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Command;

use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;

/**
 * @inheritdoc
 */
class GetSourceItemsBySkuCache implements GetSourceItemsBySkuInterface
{
    /**
     * @var GetSourceItemsBySku
     */
    private $getSourceItemsBySku;

    /**
     * @var array
     */
    private $sourceItemsBySku = [];

    /**
     * @param GetSourceItemsBySku $getSourceItemsBySku
     */
    public function __construct(GetSourceItemsBySku $getSourceItemsBySku)
    {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku): array
    {
        if (!isset($this->sourceItemsBySku[$sku])) {
            $this->sourceItemsBySku[$sku] = $this->getSourceItemsBySku->execute($sku);
        }

        return $this->sourceItemsBySku[$sku];
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model\Carrier\Command;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command\GetShippingPriceInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * @inheritdoc
 */
class GetShippingPrice implements GetShippingPriceInterface
{
    /**
     * @var GetFreePackages
     */
    private $getFreePackages;

    /**
     * @var GetConfigPrice
     */
    private $getConfigPrice;

    /**
     * @param GetFreePackages $getFreePackages
     * @param GetConfigPrice $getConfigPrice
     */
    public function __construct(
        GetFreePackages $getFreePackages,
        GetConfigPrice $getConfigPrice
    ) {
        $this->getFreePackages = $getFreePackages;
        $this->getConfigPrice = $getConfigPrice;
    }

    /**
     * @inheritdoc
     */
    public function execute(RateRequest $rateRequest): float
    {
        if ($this->getFreePackages->execute($rateRequest) === (float)$rateRequest->getPackageQty()) {
            return 0.0;
        }

        return $this->getConfigPrice->execute((int)$rateRequest->getStoreId());
    }
}

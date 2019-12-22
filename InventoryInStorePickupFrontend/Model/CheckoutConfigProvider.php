<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\SearchTerm\DelimiterConfig;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var DelimiterConfig
     */
    private $delimiterConfig;

    /**
     * @param DelimiterConfig $delimiterConfig
     */
    public function __construct(DelimiterConfig $delimiterConfig)
    {
        $this->delimiterConfig = $delimiterConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return [
            'store_pickup_api_search_term_delimiter' => $this->delimiterConfig->getDelimiter()
        ];
    }
}

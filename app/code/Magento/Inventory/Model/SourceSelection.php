<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Inventory\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Sales\Model\Order\Shipment;

class SourceSelection implements SourceSelectionInterface
{
    /** @var PackageFactory */
    private $packageFactory;

    /** @var SourceFactory */
    private $sourceFactory;

    /** @var ScopeConfigInterface  */
    private $scopeConfig;

    /**
     * @param PackageFactory $packageFactory
     * @param SourceFactory $sourceFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        PackageFactory $packageFactory,
        SourceFactory $sourceFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->packageFactory = $packageFactory;
        $this->sourceFactory = $sourceFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function getPackages($store, $items, $destinationAddress)
    {
        $countryId = $this->scopeConfig->getValue(
            Shipment::XML_PATH_STORE_COUNTRY_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $regionId = $this->scopeConfig->getValue(
            Shipment::XML_PATH_STORE_REGION_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $city = $this->scopeConfig->getValue(
            Shipment::XML_PATH_STORE_CITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $postcode = $this->scopeConfig->getValue(
            Shipment::XML_PATH_STORE_ZIP,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $source = $this->sourceFactory->create(
            [
                'data' => [
                    'source_id' => 0,
                    'name' => 'Default',
                    'country_id' => $countryId,
                    'region_id' => $regionId,
                    'city' => $city,
                    'postcode' => $postcode
                ]
            ]
        );
        return [
            $this->packageFactory->create(
                [
                    'data' => [
                        'items' => $items,
                        'source' => $source,
                        'address' => $destinationAddress
                     ]
                ]
            )
        ];
    }
}
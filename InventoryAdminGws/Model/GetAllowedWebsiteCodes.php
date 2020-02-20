<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminGws\Model;

use Magento\Store\Model\ResourceModel\Website\CollectionFactory;

/**
 * Get allowed for current user website codes service.
 */
class GetAllowedWebsiteCodes
{
    /**
     * @var CollectionFactory
     */
    private $websiteCollectionFactory;

    /**
     * @var array
     */
    private $websiteCodes = [];

    /**
     * GetAllowedWebsiteCodes constructor.
     */
    public function __construct(CollectionFactory $websiteCollectionFactory)
    {
        $this->websiteCollectionFactory = $websiteCollectionFactory;
    }

    /**
     * Get available website codes for current user.
     *
     * @return array
     */
    public function execute()
    {
        if (!$this->websiteCodes) {
            $websiteCollection = $this->websiteCollectionFactory->create();
            $websites = $websiteCollection->getItems();
            foreach ($websites as $website) {
                $this->websiteCodes[] = $website->getCode();
            }
        }

        return $this->websiteCodes;
    }
}

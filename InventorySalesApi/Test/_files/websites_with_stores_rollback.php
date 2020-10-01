<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Registry;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Magento\UrlRewrite\Model\UrlRewrite;

/** @var Registry $registry */
$registry = Bootstrap::getObjectManager()->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$websiteCodes = ['eu_website', 'us_website', 'global_website'];

foreach ($websiteCodes as $websiteCode) {
    /** @var Website $website */
    $website = Bootstrap::getObjectManager()->create(Website::class);
    $website->load($websiteCode, 'code');
    if ($website->getId()) {
        $website->delete();
    }
}

$urlRewriteCollectionFactory = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
    UrlRewriteCollectionFactory::class
);
/** @var UrlRewriteCollection $urlRewriteCollection */
$urlRewriteCollection = $urlRewriteCollectionFactory->create();
$urlRewriteCollection->addFieldToFilter('store_id', ['gt' => 1]);
$urlRewrites = $urlRewriteCollection->getItems();
/** @var UrlRewrite $urlRewrite */
foreach ($urlRewrites as $urlRewrite) {
    try {
        $urlRewrite->delete();
    } catch (\Exception $exception) {
        // already removed
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);

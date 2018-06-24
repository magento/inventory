<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

require_once 'create_quote_on_default_website_rollback.php';

/* Refresh stores memory cache */
Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->reinitStores();

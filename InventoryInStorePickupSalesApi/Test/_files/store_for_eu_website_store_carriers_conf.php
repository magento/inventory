<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\App\ApiMutableScopeConfig;
use Magento\TestFramework\Helper\Bootstrap;

$config = Bootstrap::getObjectManager()->get(
    ApiMutableScopeConfig::class
);
$config->setValue(
    'carriers/instore/active',
    '1',
    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
);
$config->setValue(
    'carriers/instore/price',
    '5.95',
    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
);

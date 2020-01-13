<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;

$config = Bootstrap::getObjectManager()->get(
    MutableScopeConfigInterface::class
);
$config->setValue(
    'carriers/in_store/active',
    0,
    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
);
$config->setValue(
    'carriers/in_store/price',
    0,
    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
);

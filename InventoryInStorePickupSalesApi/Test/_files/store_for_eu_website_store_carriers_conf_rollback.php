<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;

$config = Bootstrap::getObjectManager()->get(
    MutableScopeConfigInterface::class
);
$config->setValue(
    'carriers/instore/active',
    0,
    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
);
$config->setValue(
    'carriers/instore/price',
    0,
    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
);

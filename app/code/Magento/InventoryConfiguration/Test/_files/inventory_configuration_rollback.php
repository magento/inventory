<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

// Force removal of data from inventory_configuration table because services don't allow to delete entries,
// they only allow us to set them null; this way we force a real rollback

/** @var \Magento\Framework\App\ResourceConnection $resourceConnection */
$resourceConnection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
/** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
$connection = $resourceConnection->getConnection();
$configurationTable = $resourceConnection->getTableName('inventory_configuration');
$connection->delete($configurationTable);

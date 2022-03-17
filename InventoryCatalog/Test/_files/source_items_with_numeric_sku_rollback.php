<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var SourceItemRepositoryInterface $sourceItemRepository */
$sourceItemRepository = $objectManager->get(SourceItemRepositoryInterface::class);
/** @var SourceItemsDeleteInterface $sourceItemsDelete */
$sourceItemsDelete = $objectManager->get(SourceItemsDeleteInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

$searchCriteria = $searchCriteriaBuilder->addFilter(
    SourceItemInterface::SKU,
    ['01234', '1234'],
    'in'
)->create();
$sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();

if (!empty($sourceItems)) {
    $sourceItemsDelete->execute($sourceItems);
}

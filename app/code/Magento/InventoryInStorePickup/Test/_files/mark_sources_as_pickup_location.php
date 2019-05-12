<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$om = Bootstrap::getObjectManager();
/** @var SourceRepositoryInterface $sourceRepository */
$sourceRepository = $om->get(SourceRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $om->get(SearchCriteriaBuilder::class);

$sources = $sourceRepository->getList(
    $searchCriteriaBuilder->addFilter(SourceInterface::SOURCE_CODE, 'eu-1,eu-2', 'in')->create()
)->getItems();

foreach ($sources as $source) {
    $source->getExtensionAttributes()->setIsPickupLocationActive(true);
    $sourceRepository->save($source);
}

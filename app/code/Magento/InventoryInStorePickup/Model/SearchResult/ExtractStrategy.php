<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchResult;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchResult\ExtractStrategyInterface;

/**
 * @inheritdoc
 */
class ExtractStrategy implements ExtractStrategyInterface
{
    /**
     * @var DistanceBasedStrategy
     */
    private $distanceBasedStrategy;

    /**
     * @param DistanceBasedStrategy $distanceBasedStrategy
     */
    public function __construct(
        DistanceBasedStrategy $distanceBasedStrategy
    ) {
        $this->distanceBasedStrategy = $distanceBasedStrategy;
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function getSources(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): array {
        if ($searchRequest->getDistanceFilter()) {
            $data = $this->distanceBasedStrategy->getSources($searchRequest, $sourcesSearchResult);
        } else {
            // default strategy
            $data = $sourcesSearchResult->getItems();
        }

        return $data;
    }
}

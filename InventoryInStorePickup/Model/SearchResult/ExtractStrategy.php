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
     * @var RequestBasedStrategy
     */
    private $requestBasedStrategy;

    /**
     * @param DistanceBasedStrategy $distanceBasedStrategy
     * @param RequestBasedStrategy $requestBasedStrategy
     */
    public function __construct(
        DistanceBasedStrategy $distanceBasedStrategy,
        RequestBasedStrategy $requestBasedStrategy
    ) {
        $this->distanceBasedStrategy = $distanceBasedStrategy;
        $this->requestBasedStrategy = $requestBasedStrategy;
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     */
    public function getSources(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): array {
        if ($searchRequest->getDistanceFilter()) {
            $data = $this->distanceBasedStrategy->getSources($searchRequest, $sourcesSearchResult);
        } else {
            $data = $this->requestBasedStrategy->getSources($searchRequest, $sourcesSearchResult);
        }

        return $data;
    }
}

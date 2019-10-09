<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchResult;

use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * @inheritdoc
 */
class Extractor implements ExtractorInterface
{
    /**
     * @var StrategyInterface[]
     */
    private $strategies;

    /**
     * @param StrategyInterface[] $strategies
     */
    public function __construct(array $strategies)
    {
        $this->validateStrategies($strategies);
        $this->strategies = $strategies;
    }

    /**
     * @inheritdoc
     */
    public function getSources(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): array {
        foreach ($this->strategies as $strategy) {
            if ($strategy->isApplicable($searchRequest, $sourcesSearchResult)) {
                return $strategy->getSources($searchRequest, $sourcesSearchResult);
            }
        }

        // just return items if there are no applicable strategy
        return $sourcesSearchResult->getItems();
    }

    /**
     * Validate input of strategies.
     *
     * @param array $strategies
     *
     * @throws \InvalidArgumentException
     */
    private function validateStrategies(array $strategies): void
    {
        foreach ($strategies as $strategy) {
            if (!$strategy instanceof StrategyInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Extract Strategy must implement %s.' .
                        '%s has been received instead.',
                        StrategyInterface::class,
                        get_class($strategy)
                    )
                );
            }
        }
    }
}

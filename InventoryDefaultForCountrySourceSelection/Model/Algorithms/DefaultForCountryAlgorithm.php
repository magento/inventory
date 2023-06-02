<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDefaultForCountrySourceSelection\Model\Algorithms;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryDefaultForCountrySourceSelection\Model\Configuration;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\InventorySourceSelectionApi\Model\Algorithms\Result\GetDefaultSortedSourcesResult;
use Magento\InventorySourceSelectionApi\Model\SourceSelectionInterface;

/**
 * This shipping algorithm just iterates over all the sources one by one in distance order
 */
class DefaultForCountryAlgorithm implements SourceSelectionInterface
{
    /**
     * Algorithm code
     */
    public const CODE = 'default_for_countries';

    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var GetDefaultSortedSourcesResult
     */
    private $getDefaultSortedSourcesResult;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * DefaultForCountryAlgorithm constructor.
     *
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param GetDefaultSortedSourcesResult $getDefaultSortedSourcesResult
     * @param Configuration $configuration
     * @param SourceSelectionServiceInterface $sourceSelectionService
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        GetDefaultSortedSourcesResult $getDefaultSortedSourcesResult,
        Configuration $configuration,
        SourceSelectionServiceInterface $sourceSelectionService
    ) {
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->getDefaultSortedSourcesResult = $getDefaultSortedSourcesResult;
        $this->configuration = $configuration;
        $this->sourceSelectionService = $sourceSelectionService;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function execute(InventoryRequestInterface $inventoryRequest): SourceSelectionResultInterface
    {
        $destinationAddress = $inventoryRequest->getExtensionAttributes()->getDestinationAddress();
        if ($destinationAddress === null) {
            throw new LocalizedException(__('No destination address was provided in the request'));
        }

        $stockId = $inventoryRequest->getStockId();
        $sortedSources = $this->getEnabledSourcesOrderedByDefaultForCountriesByStockId(
            $stockId,
            $destinationAddress,
            $inventoryRequest
        );

        return $this->getDefaultSortedSourcesResult->execute($inventoryRequest, $sortedSources);
    }

    /**
     * Get enabled sources ordered by countries and fallback algorithm by $stockId
     *
     * @param int $stockId
     * @param AddressInterface $address
     * @param InventoryRequestInterface $inventoryRequest
     * @return array
     * @throws InputException
     * @throws LocalizedException
     */
    private function getEnabledSourcesOrderedByDefaultForCountriesByStockId(
        int $stockId,
        AddressInterface $address,
        InventoryRequestInterface $inventoryRequest
    ): array {
        $priorityBySourceCode = $sortSources = $sourcesFromAdditional = [];

        $additionalAlgorithmCode = $this->configuration->getAdditionalAlgorithmCode();
        if (!empty($additionalAlgorithmCode)) {
            $additionalAlgorithmResult = $this->sourceSelectionService->execute(
                $inventoryRequest,
                $additionalAlgorithmCode
            );
            $sourceSelectionItemsFromAdditional = $additionalAlgorithmResult->getSourceSelectionItems();
            $i = 1;
            foreach ($sourceSelectionItemsFromAdditional as $sourceSelectionItem) {
                $sourcesFromAdditional[$sourceSelectionItem->getSourceCode()] = $i++;
            }
        }

        // Keep priority order as computational base
        $sources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);
        $sources = array_filter($sources, function (SourceInterface $source) {
            return $source->isEnabled();
        });

        $isExcludeUnmatchedEnabled = $this->configuration->isExcludeUnmatchedEnabled();
        $defaultSortOrder = count($sourcesFromAdditional) + count($sources);
        foreach ($sources as $source) {
            // Keep default sort order big, so source from default for countries can be pushed to start of array
            $sortOrder = $defaultSortOrder;
            $defaultForCountries = $source->getExtensionAttributes()->getDefaultForCountries();

            $countryMatchToSourceFlag = isset($defaultForCountries)
                && in_array($address->getCountry(), $defaultForCountries);
            if ($countryMatchToSourceFlag) {
                // push default for country source to start of array
                $sortOrder = 0;
            }

            if ($isExcludeUnmatchedEnabled && !$countryMatchToSourceFlag) {
                continue;
            }

            if (isset($sourcesFromAdditional[$source->getSourceCode()])) {
                // increase sort order based on sort order from additional algorithm
                $sortOrder += $sourcesFromAdditional[$source->getSourceCode()];
            }

            $priorityBySourceCode[$source->getSourceCode()] = $sortOrder;
            $sortSources[] = $source;
        }

        // Sort sources by priority
        uasort(
            $sortSources,
            function (SourceInterface $a, SourceInterface $b) use ($priorityBySourceCode) {
                $priorityA = $priorityBySourceCode[$a->getSourceCode()];
                $priorityB = $priorityBySourceCode[$b->getSourceCode()];

                return ($priorityA < $priorityB) ? -1 : 1;
            }
        );

        return $sortSources;
    }
}

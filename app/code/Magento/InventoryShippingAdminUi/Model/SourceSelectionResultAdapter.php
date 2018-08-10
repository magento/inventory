<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Model;

use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;

/**
 * this class should be created from @see SourceSelectionResultAdapterFromRequestItemsFactory
 * Adapter of SourceSelectionResult for DataProvider
 */
class SourceSelectionResultAdapter
{
    /**
     * @var SourceSelectionResultInterface
     */
    private $sourceSelectionResult;

    /**
     * @var SortSourcesAfterSourceSelectionAlgorithm
     */
    private $sortSourcesAfterSourceSelectionAlgorithm;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var array [source_code => 'source name']
     */
    private $sources = [];

    /**
     * SourceSelectionResultAdapter constructor.
     *
     * @param SortSourcesAfterSourceSelectionAlgorithm $sortSourcesAfterSourceSelectionAlgorithm
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceSelectionResultInterface|null $sourceSelectionResult
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        SortSourcesAfterSourceSelectionAlgorithm $sortSourcesAfterSourceSelectionAlgorithm,
        SourceRepositoryInterface $sourceRepository,
        SourceSelectionResultInterface $sourceSelectionResult = null
    ) {
        if (false === $sourceSelectionResult instanceof SourceSelectionResultInterface) {
            throw new \Magento\Framework\Exception\LocalizedException(__('$sourceSelectionResult is required'));
        }
        $this->sourceSelectionResult = $sourceSelectionResult;
        $this->sortSourcesAfterSourceSelectionAlgorithm = $sortSourcesAfterSourceSelectionAlgorithm;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @param string $sku
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSkuSources(string $sku): array
    {
        $result = [];
        foreach ($this->sourceSelectionResult->getSourceSelectionItems() as $item) {
            if ($item->getSku() === $sku) {
                $sourceCode = $item->getSourceCode();
                $result[] = [
                    'sourceName'   => $this->getSourceName($sourceCode),
                    'sourceCode'   => $sourceCode,
                    'qtyAvailable' => $item->getQtyAvailable(),
                    'qtyToDeduct'  => $item->getQtyToDeduct()
                ];
            }
        }

        return $result;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSources(): array
    {
        $result = [];

        $sourceCodes = $this->sortSourcesAfterSourceSelectionAlgorithm->execute($this->sourceSelectionResult);
        foreach ($sourceCodes as $sourceCode) {
            $result[] = [
                'value' => $sourceCode,
                'label' => $this->getSourceName($sourceCode)
            ];
        }

        return $result;
    }

    /**
     * Get source name by code
     *
     * @param string $sourceCode
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getSourceName(string $sourceCode): string
    {
        if (!isset($this->sources[$sourceCode])) {
            $this->sources[$sourceCode] = $this->sourceRepository->get($sourceCode)->getName();
        }

        return $this->sources[$sourceCode];
    }
}

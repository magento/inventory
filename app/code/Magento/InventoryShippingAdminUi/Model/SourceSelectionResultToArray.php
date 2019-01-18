<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Model;

use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class SourceSelectionResultToArray
{
    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var array
     */
    private $sources = [];

    /**
     * @param SourceSelectionServiceInterface $sourceSelectionService
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        SourceSelectionServiceInterface $sourceSelectionService,
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->sourceSelectionService = $sourceSelectionService;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @param InventoryRequestInterface $inventoryRequest
     * @param string $algorithmCode
     * @return array
     */
    public function execute(InventoryRequestInterface $inventoryRequest, string $algorithmCode)
    {
        $sourceSelectionResult = $this->sourceSelectionService->execute(
            $inventoryRequest,
            $algorithmCode
        );

        $result = [];

        foreach ($sourceSelectionResult->getSourceSelectionItems() as $item) {
            $sourceCode = $item->getSourceCode();
            $result[] = [
                'sourceName' => $this->getSourceName($sourceCode),
                'sourceCode' => $sourceCode,
                'qtyAvailable' => $item->getQtyAvailable(),
                'qtyToDeduct' => $item->getQtyToDeduct()
            ];
        }

        return $result;
    }

    /**
     * Get source name by code
     *
     * @param string $sourceCode
     * @return mixed
     * @throws NoSuchEntityException
     */
    private function getSourceName(string $sourceCode): string
    {
        if (!isset($this->sources[$sourceCode])) {
            $this->sources[$sourceCode] = $this->sourceRepository->get($sourceCode)->getName();
        }

        return $this->sources[$sourceCode];
    }
}

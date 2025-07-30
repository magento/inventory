<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Ui\DataProvider;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Fetches source data by stock ID, SKU, and quantity, using the default source selection algorithm
 * to determine availability and deduction details.
 *
 * @deprecated
 * @see \Magento\InventoryShippingAdminUi\Ui\DataProvider\GetSourcesByOrderIdStockIdSkuAndQty
 */
class GetSourcesByStockIdSkuAndQty
{
    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @var InventoryRequestInterfaceFactory
     */
    private $inventoryRequestFactory;

    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * @var GetDefaultSourceSelectionAlgorithmCodeInterface
     */
    private $getDefaultSourceSelectionAlgorithmCode;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var array
     */
    private $sources = [];

    /**
     * GetSourcesByStockIdSkuAndQty constructor.
     *
     * @param ItemRequestInterfaceFactory $itemRequestFactory
     * @param InventoryRequestInterfaceFactory $inventoryRequestFactory
     * @param SourceSelectionServiceInterface $sourceSelectionService
     * @param GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode
     * @param SourceRepositoryInterface $sourceRepository
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        ItemRequestInterfaceFactory $itemRequestFactory,
        InventoryRequestInterfaceFactory $inventoryRequestFactory,
        SourceSelectionServiceInterface $sourceSelectionService,
        GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode,
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->itemRequestFactory = $itemRequestFactory;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * Processes stock, SKU, and quantity to fetch source data using the default source selection algorithm.
     *
     * @param int $stockId
     * @param string $sku
     * @param float $qty
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function execute(int $stockId, string $sku, float $qty): array
    {
        $algorithmCode = $this->getDefaultSourceSelectionAlgorithmCode->execute();

        $requestItem = $this->itemRequestFactory->create([
            'sku' => $sku,
            'qty' => $qty
        ]);
        $inventoryRequest = $this->inventoryRequestFactory->create([
            'stockId' => $stockId,
            'items' => [$requestItem]
        ]);
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

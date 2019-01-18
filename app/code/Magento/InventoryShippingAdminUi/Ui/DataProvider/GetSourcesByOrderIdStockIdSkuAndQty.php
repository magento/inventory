<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Ui\DataProvider;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryShippingAdminUi\Model\SourceSelectionResultToArray;
use Magento\InventorySourceSelectionApi\Exception\UndefinedInventoryRequestBuilderException;
use Magento\InventorySourceSelectionApi\Model\GetInventoryRequestFromOrderBuilder;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Class GetSourcesByOrderIdStockIdSkuAndQty
 * @package Magento\InventoryShippingAdminUi\Ui\DataProvider
 */
class GetSourcesByOrderIdStockIdSkuAndQty
{
    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

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
     * @var GetInventoryRequestFromOrderBuilder
     */
    private $getInventoryRequestFromOrderBuilder;

    /**
     * @var SourceSelectionResultToArray
     */
    private $sourceSelectionResultToArray;

    /**
     * GetSourcesByStockIdSkuAndQty constructor.
     *
     * @param ItemRequestInterfaceFactory $itemRequestFactory
     * @param SourceSelectionServiceInterface $sourceSelectionService
     * @param GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode
     * @param GetInventoryRequestFromOrderBuilder $getInventoryRequestFromOrderBuilder
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceSelectionResultToArray $sourceSelectionResultToArray
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        ItemRequestInterfaceFactory $itemRequestFactory,
        SourceSelectionServiceInterface $sourceSelectionService,
        GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode,
        GetInventoryRequestFromOrderBuilder $getInventoryRequestFromOrderBuilder,
        SourceRepositoryInterface $sourceRepository,
        SourceSelectionResultToArray $sourceSelectionResultToArray
    ) {
        $this->itemRequestFactory = $itemRequestFactory;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        $this->sourceRepository = $sourceRepository;
        $this->getInventoryRequestFromOrderBuilder = $getInventoryRequestFromOrderBuilder;
        $this->sourceSelectionResultToArray = $sourceSelectionResultToArray;
    }

    /**
     * Get sources by stock id sku and qty
     *
     * @param int $orderId
     * @param int $stockId
     * @param string $sku
     * @param float $qty
     * @return array
     * @throws NoSuchEntityException
     * @throws UndefinedInventoryRequestBuilderException
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function execute(int $orderId, int $stockId, string $sku, float $qty): array
    {
        $algorithmCode = $this->getDefaultSourceSelectionAlgorithmCode->execute();

        $requestItem = $this->itemRequestFactory->create([
            'sku' => $sku,
            'qty' => $qty
        ]);

        $inventoryRequestBuilder = $this->getInventoryRequestFromOrderBuilder->execute($algorithmCode);
        $inventoryRequest = $inventoryRequestBuilder->execute($stockId, $orderId, [$requestItem]);

        return $this->sourceSelectionResultToArray->execute($inventoryRequest, $algorithmCode);
    }
}

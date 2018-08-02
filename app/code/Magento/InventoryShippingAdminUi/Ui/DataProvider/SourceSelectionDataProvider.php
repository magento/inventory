<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventorySourceSelectionApi\Api\GetDefaultSourceSelectionAlgorithmCodeInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\Framework\App\RequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\SourceSelectionServiceInterface;
use Magento\InventoryShippingAdminUi\Model\SortSourcesAfterSourceSelectionAlgorithm;

class SourceSelectionDataProvider extends AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var SourceSelectionServiceInterface
     */
    private $sourceSelectionService;

    /**
     * @var GetDefaultSourceSelectionAlgorithmCodeInterface
     */
    private $getDefaultSourceSelectionAlgorithmCode;

    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @var InventoryRequestInterfaceFactory
     */
    private $inventoryRequestFactory;

    /**
     * @var SortSourcesAfterSourceSelectionAlgorithm
     */
    private $sortSourcesAfterSourceSelectionAlgorithm;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var array
     */
    private $sources = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param RequestInterface $request
     * @param OrderRepositoryInterface $orderRepository
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param SourceSelectionServiceInterface $sourceSelectionService
     * @param GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode
     * @param ItemRequestInterfaceFactory $itemRequestFactory
     * @param InventoryRequestInterfaceFactory $inventoryRequestFactory
     * @param SourceRepositoryInterface $sourceRepository
     * @param SortSourcesAfterSourceSelectionAlgorithm $sortSourcesAfterSourceSelectionAlgorithm
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        SourceSelectionServiceInterface $sourceSelectionService,
        GetDefaultSourceSelectionAlgorithmCodeInterface $getDefaultSourceSelectionAlgorithmCode,
        ItemRequestInterfaceFactory $itemRequestFactory,
        InventoryRequestInterfaceFactory $inventoryRequestFactory,
        SourceRepositoryInterface $sourceRepository,
        SortSourcesAfterSourceSelectionAlgorithm $sortSourcesAfterSourceSelectionAlgorithm,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->sourceSelectionService = $sourceSelectionService;
        $this->getDefaultSourceSelectionAlgorithmCode = $getDefaultSourceSelectionAlgorithmCode;
        $this->itemRequestFactory = $itemRequestFactory;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
        $this->sourceRepository = $sourceRepository;
        $this->sortSourcesAfterSourceSelectionAlgorithm = $sortSourcesAfterSourceSelectionAlgorithm;
    }

    /**
     * Disable for collection processing | ????
     *
     * @param Filter $filter
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addFilter(Filter $filter)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getData(): array
    {
        $data = [];
        $orderId = $this->request->getParam('order_id');
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);
        $websiteId = $order->getStore()->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();

        foreach ($order->getAllItems() as $orderItem) {
            if ($orderItem->getIsVirtual()
                || $orderItem->getLockedDoShip()
                || $orderItem->getHasChildren()) {
                continue;
            }

            $orderItemId = $orderItem->getId();
            //TODO: Need to add additional logic for bundle product with flag ship Together
            if ($orderItem->getParentItem() && !$orderItem->isShipSeparately()) {
                $orderItemId = $orderItem->getParentItemId();
            }

            $qty = $orderItem->getSimpleQtyToShip();
            $qty = $this->castQty($orderItem, $qty);
            $sku = $orderItem->getSku();

            $data[$orderId]['items'][] = [
                'orderItemId' => $orderItemId,
                'sku' => $sku,
                'product' => $this->getProductName($orderItem),
                'qtyToShip' => $qty,
                'sources' => [],
                'isManageStock' => $this->isManageStock($sku, $stockId)
            ];
        }
        $data[$orderId]['websiteId'] = $websiteId;
        $data[$orderId]['order_id'] = $orderId;

        $this->assignSources($data[$orderId], $stockId);

        return $data;
    }

    /**
     * @param array $data
     * @param int $stockId
     */
    private function assignSources(array &$data, int $stockId): void
    {
        /** @var \Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterface[] $requestItems */
        $requestItems = [];
        foreach ($data['items'] as $dataRow) {
            $requestItems[] = $this->itemRequestFactory->create([
                'sku' => $dataRow['sku'],
                'qty' => $dataRow['qtyToShip']
            ]);
        }

        /** @var \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface $sourceSelectionResult */
        $inventoryRequest = $this->inventoryRequestFactory->create([
            'stockId' => $stockId,
            'items'   => $requestItems
        ]);

        $sourceSelectionResult = $this->sourceSelectionService->execute(
            $inventoryRequest,
            $this->getDefaultSourceSelectionAlgorithmCode->execute()
        );

        foreach ($data['items'] as &$dataRow) {
            foreach ($sourceSelectionResult->getSourceSelectionItems() as $item) {
                if ($item->getSku() === $dataRow['sku']) {
                    $sourceCode = $item->getSourceCode();
                    $dataRow['sources'][] = [
                        'sourceName' => $this->getSourceName($sourceCode),
                        'sourceCode' => $sourceCode,
                        'qtyAvailable' => $item->getQtyAvailable(),
                        'qtyToDeduct' => $item->getQtyToDeduct()
                    ];
                }
            }
        }

        $sourceCodes = $this->sortSourcesAfterSourceSelectionAlgorithm->execute($sourceSelectionResult);
        foreach ($sourceCodes as $sourceCode) {
            $data['sourceCodes'][] = [
                'value' => $sourceCode,
                'label' => $this->getSourceName($sourceCode)
            ];
        }
    }

    /**
     * Get source name by code
     *
     * @param string $sourceCode
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

    /**
     * @param string $itemSku
     * @param int $stockId
     * @return bool
     * @throws LocalizedException
     */
    private function isManageStock(string $itemSku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($itemSku, $stockId);

        return $stockItemConfiguration->isManageStock();
    }

    /**
     * Generate display product name
     * @param Item $item
     * @return null|string
     */
    private function getProductName(Item $item)
    {
        //TODO: need to transfer this to html block and render on Ui
        $name = $item->getName();
        if ($parentItem = $item->getParentItem()) {
            $name = $parentItem->getName();
            $options = [];
            if ($productOptions = $parentItem->getProductOptions()) {
                if (isset($productOptions['options'])) {
                    $options = array_merge($options, $productOptions['options']);
                }
                if (isset($productOptions['additional_options'])) {
                    $options = array_merge($options, $productOptions['additional_options']);
                }
                if (isset($productOptions['attributes_info'])) {
                    $options = array_merge($options, $productOptions['attributes_info']);
                }
                if (count($options)) {
                    foreach ($options as $option) {
                        $name .= '<dd>' . $option['label'] . ': ' . $option['value'] .'</dd>';
                    }
                } else {
                    $name .= '<dd>' . $item->getName() . '</dd>';
                }
            }
        }

        return $name;
    }

    /**
     * @param Item $item
     * @param string|int|float $qty
     * @return float|int
     */
    private function castQty(Item $item, $qty)
    {
        if ($item->getIsQtyDecimal()) {
            $qty = (double)$qty;
        } else {
            $qty = (int)$qty;
        }

        return $qty > 0 ? $qty : 0;
    }
}

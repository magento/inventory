<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Model\Stock;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;

/**
 * Save stock processor for save stock controller
 */
class StockSaveProcessor
{
    /**
     * @var StockInterfaceFactory
     */
    private $stockFactory;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var StockSourceLinkProcessor
     */
    private $stockSourceLinkProcessor;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param StockInterfaceFactory $stockFactory
     * @param StockRepositoryInterface $stockRepository
     * @param StockSourceLinkProcessor $stockSourceLinkProcessor
     * @param DataObjectHelper $dataObjectHelper
     * @param EventManager $eventManager
     * @param CollectionFactory|null $websiteCollection
     */
    public function __construct(
        StockInterfaceFactory $stockFactory,
        StockRepositoryInterface $stockRepository,
        StockSourceLinkProcessor $stockSourceLinkProcessor,
        DataObjectHelper $dataObjectHelper,
        EventManager $eventManager,
        CollectionFactory $websiteCollection = null
    ) {
        $this->stockFactory = $stockFactory;
        $this->stockRepository = $stockRepository;
        $this->stockSourceLinkProcessor = $stockSourceLinkProcessor;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->eventManager = $eventManager;
        $this->collectionFactory = $websiteCollection ?: ObjectManager::getInstance()->get(CollectionFactory::class);
    }

    /**
     * Save stock process action
     *
     * @param int|null $stockId
     * @param RequestInterface $request
     * @return int
     * @throws LocalizedException
     */
    public function process($stockId, RequestInterface $request): int
    {
        if (null === $stockId) {
            $stock = $this->stockFactory->create();
        } else {
            $stock = $this->stockRepository->get($stockId);
            $this->verifySalesChannels($stock);
        }
        $requestData = $request->getParams();
        $this->dataObjectHelper->populateWithArray($stock, $requestData['general'], StockInterface::class);
        $this->eventManager->dispatch(
            'controller_action_inventory_populate_stock_with_data',
            [
                'request' => $request,
                'stock' => $stock,
            ]
        );
        $stockId = $this->stockRepository->save($stock);
        $this->eventManager->dispatch(
            'save_stock_controller_processor_after_save',
            [
                'request' => $request,
                'stock' => $stock,
            ]
        );

        $assignedSources =
            isset($requestData['sources']['assigned_sources'])
            && is_array($requestData['sources']['assigned_sources'])
                ? $this->prepareAssignedSources($requestData['sources']['assigned_sources'])
                : [];
        $this->stockSourceLinkProcessor->process($stockId, $assignedSources);

        return $stockId;
    }

    /**
     * Convert built-in UI component property position into priority
     *
     * @param array $assignedSources
     * @return array
     */
    private function prepareAssignedSources(array $assignedSources): array
    {
        foreach ($assignedSources as $key => $source) {
            if (empty($source['priority'])) {
                $source['priority'] = (int)$source['position'];
                $assignedSources[$key] = $source;
            }
        }
        return $assignedSources;
    }

    /**
     * Verify sales channels could be managed by admin.
     *
     * @param StockInterface $stock
     * @throws CouldNotSaveException
     * @return
     */
    private function verifySalesChannels(StockInterface $stock): void
    {
        $codes = [];
        $allCodes = [];
        $websites = $this->collectionFactory->create()->getItems();
        $websitesData = $this->collectionFactory->create()->getData();
        foreach ($websitesData as $websiteData) {
            $allCodes[] = $websiteData['code'];
        }
        foreach ($websites as $website) {
            $codes[] = $website->getCode();
        }
        $salesChannels = $stock->getExtensionAttributes()->getSalesChannels();
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel->getType() === SalesChannelInterface::TYPE_WEBSITE
                && !in_array($salesChannel->getCode(), $codes) && in_array($salesChannel->getCode(), $allCodes)) {
                throw new CouldNotSaveException(__('Not enough permissions to save stock.'));
            }
        }
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\TestFramework\Fixture\RevertibleDataFixtureInterface;

class StockSalesChannels implements RevertibleDataFixtureInterface
{
    private const DEFAULT_DATA = [
        'stock_id' => null,
        'sales_channels' => [

        ]
    ];

    /**
     * @var StockRepositoryInterface
     */
    private StockRepositoryInterface $stockRepository;

    /**
     * @var SalesChannelInterfaceFactory
     */
    private SalesChannelInterfaceFactory $salesChannelFactory;

    /**
     * @param StockRepositoryInterface $stockRepository
     * @param SalesChannelInterfaceFactory $salesChannelFactory
     */
    public function __construct(
        StockRepositoryInterface $stockRepository,
        SalesChannelInterfaceFactory $salesChannelFactory,
    ) {
        $this->stockRepository = $stockRepository;
        $this->salesChannelFactory = $salesChannelFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters
     * <pre>
     *    $data = [
     *      'stock_id' => (int) Cart ID. Required
     *      'sales_channels' => (array) Array of data of type SalesChannelInterface OR array of website codes
     *    ]
     * </pre>
     */
    public function apply(array $data = []): ?DataObject
    {
        $stock = $this->stockRepository->get($data['stock_id']);
        $extensionAttributes = $stock->getExtensionAttributes();
        $salesChannels = [];

        foreach ($data['sales_channels'] as $salesChannel) {
            if (is_array($salesChannel)) {
                $salesChannelData = $salesChannel + ['type' => SalesChannelInterface::TYPE_WEBSITE];
            } else {
                $salesChannelData = ['code' => $salesChannel, 'type' => SalesChannelInterface::TYPE_WEBSITE];
            }
            $salesChannels[] = $this->salesChannelFactory->create(['data' => $salesChannelData]);
        }

        $extensionAttributes->setSalesChannels($salesChannels);
        $this->stockRepository->save($stock);

        return $stock;
    }

    /**
     * @inheritdoc
     */
    public function revert(DataObject $data): void
    {
        $stock = $this->stockRepository->get((int) $data['stock_id']);
        $extensionAttributes = $stock->getExtensionAttributes();
        $extensionAttributes->setSalesChannels([]);
        $this->stockRepository->save($stock);
    }
}

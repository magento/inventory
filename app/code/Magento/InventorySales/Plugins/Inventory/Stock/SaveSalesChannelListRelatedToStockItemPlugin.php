<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugins\Inventory\Stock;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySales\Model\ReplaceSalesChannelsForStockInterface;
use Psr\Log\LoggerInterface;

/**
 * Save SalesChannel List Related to StockItem on StockRepositoryInterface::save()
 */
class SaveSalesChannelListRelatedToStockItemPlugin
{
    /**
     * @var ReplaceSalesChannelsForStockInterface
     */
    private $replaceSalesChannelsOnStock;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SaveSalesChannelsLinksPlugin constructor.
     *
     * @param ReplaceSalesChannelsForStockInterface $replaceSalesChannelsOnStock
     * @param LoggerInterface $logger
     */
    public function __construct(
        ReplaceSalesChannelsForStockInterface $replaceSalesChannelsOnStock,
        LoggerInterface $logger
    ) {
        $this->replaceSalesChannelsOnStock = $replaceSalesChannelsOnStock;
        $this->logger = $logger;
    }

    /**
     * Saves Sales Channel Link for Stock
     *
     * @param StockRepositoryInterface $subject
     * @param callable $proceed
     * @param StockInterface $stock
     *
     * @return int
     * @throws CouldNotSaveException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        StockRepositoryInterface $subject,
        callable $proceed,
        StockInterface $stock
    ): int {
        $extensionAttributes = $stock->getExtensionAttributes();
        $salesChannelList = $extensionAttributes->getSalesChannels();

        $stockId = $proceed($stock);

        if (null !== $salesChannelList) {
            $this->saveSalesChannelListRelatedToStockId($salesChannelList, $stockId);
        }

        return $stockId;
    }

    /**
     * Save SalesChannel List Related to StockItem
     *
     * @param array $salesChannelList
     * @param int $stockId
     *
     * @throws CouldNotSaveException
     */
    private function saveSalesChannelListRelatedToStockId(array $salesChannelList, int $stockId)
    {
        try {
            $this->replaceSalesChannelsOnStock->execute($salesChannelList, $stockId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not replace Sales Channels for Stock'), $e);
        }
    }
}

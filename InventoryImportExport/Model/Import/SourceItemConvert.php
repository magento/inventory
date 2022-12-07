<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import;

use Magento\Framework\App\ObjectManager;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

class SourceItemConvert
{
    public const QTY = 'qty';

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param DefaultSourceProviderInterface|null $defaultSourceProvider
     */
    public function __construct(
        SourceItemInterfaceFactory $sourceItemFactory,
        ?DefaultSourceProviderInterface $defaultSourceProvider = null
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->defaultSourceProvider = $defaultSourceProvider
            ?? ObjectManager::getInstance()->get(DefaultSourceProviderInterface::class);
    }

    /**
     * Converts a data in sourceItem list.
     *
     * @param array $bunch
     * @return SourceItemInterface[]
     */
    public function convert(array $bunch): array
    {
        $sourceItems = [];
        foreach ($bunch as $rowData) {
            /** @var SourceItemInterface $sourceItem */
            $sourceItem = $this->sourceItemFactory->create();
            $sourceCode = $rowData[Sources::COL_SOURCE_CODE] ?? $this->defaultSourceProvider->getCode();
            $sourceItem->setSourceCode($sourceCode);
            $sourceItem->setSku($rowData[Sources::COL_SKU]);
            if (isset($rowData[Sources::COL_QTY])) {
                $sourceQuantity = $rowData[Sources::COL_QTY];
            } elseif (isset($rowData[self::QTY])) {
                $sourceQuantity = $rowData[self::QTY];
            } else {
                $sourceQuantity = 0;
            }
            $sourceItem->setQuantity((float)$sourceQuantity);
            $status = $rowData[Sources::COL_STATUS] ?? 1;
            $sourceItem->setStatus((int)$status);

            $sourceItems[] = $sourceItem;
        }

        return $sourceItems;
    }
}

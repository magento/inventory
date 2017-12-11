<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Source\Item\Importer;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\SourceItem;
use Magento\Inventory\Model\SourceItemFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;

class MultiSourceProcessor
{
    /**
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSourceProvider
     */
    private $defaultSourceProvider;

    /**
     * Source Item Interface Factory
     *
     * @var SourceItemFactory $sourceItemFactory
     */
    private $sourceItemFactory;

    /**
     * Source Repository Interface
     *
     * @var SourceRepositoryInterface $sourceRepositoryInterface
     */
    private $sourceRepositoryInterface;

    /**
     * StockItemImporter constructor
     *
     * @param SourceItemFactory $sourceItemFactory
     * @param SourceRepositoryInterface $sourceRepositoryInterface
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        SourceItemFactory $sourceItemFactory,
        SourceRepositoryInterface $sourceRepositoryInterface,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceRepositoryInterface = $sourceRepositoryInterface;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param array $data
     * @param string|int $rowNumber
     * @return SourceItemInterface[]|bool
     */
    public function execute(array $data, $rowNumber)
    {
        if ($splitSourceData = $this->getSplitSourceData($data, $rowNumber)) {
            $sourceItems = [];
            /** @var array $splitSourceDatum */
            foreach ($splitSourceData as $splitSourceDatum) {
                $inStock = $splitSourceDatum['is_in_stock'];
                /** @var SourceItem $sourceItem */
                $sourceItem = $this->sourceItemFactory->create();
                $sourceItem->setSku($data[Product::COL_SKU]);
                $sourceItem->setSourceId($splitSourceDatum['source']);
                $sourceItem->setQuantity($splitSourceDatum['qty']);
                $sourceItem->setStatus($inStock);
                $sourceItems[] = $sourceItem;
            }
            return $sourceItems;
        }
        return false;
    }

    /**
     * Return Source Data foreach qty value
     *
     * @param array $data
     * @param string|int $rowNumber
     * @return array|bool
     */
    public function getSplitSourceData(array $data, $rowNumber)
    {
        if (strpos($data['qty'], '|') !== false) {
            $sourceData = [];
            $sources = explode('|', $data['qty']);
            if (isset($data['is_in_stock']) && !is_numeric($data['is_in_stock'])) {
                $inStock = explode('|', $data['is_in_stock']);
            }
            foreach ($sources as $source) {
                $individualSourceData = explode('=', $source);
                if ($individualSourceData[0] == 'default') {
                    $sourceId = $this->defaultSourceProvider->getId();
                } else {
                    $sourceId = $this->getSource($individualSourceData[0], $rowNumber)->getSourceId();
                }
                $sourceInStock = 0;
                if (isset($inStock) && !empty($inStock)) {
                    foreach ($inStock as $value) {
                        if (!is_numeric($value) && strpos($value, '=') !== false) {
                            $inStockVal = explode('=', $value);
                            if ($inStockVal[0] == $sourceId) {
                                $sourceInStock = $inStockVal[1];
                                continue;
                            }
                        } else {
                            $sourceInStock = $value;
                            continue;
                        }
                    }
                }
                $sourceData[] = [
                    'source' => $sourceId,
                    'qty' => $individualSourceData[1],
                    'is_in_stock' => $sourceInStock
                ];
            }
            return $sourceData;
        }
        return false;
    }

    /**
     * Return Source ID if source exists with given id
     *
     * @param string|int $qty
     * @param string|int $rowNumber
     * @return bool|SourceInterface
     * @throws ValidationException
     */
    private function getSource($qty, $rowNumber)
    {
        $parts = explode('=', $qty);
        $sourceId = (int)$parts[0];
        try {
            /** @var SourceInterface $source */
            $source = $this->sourceRepositoryInterface->get($sourceId);
            return $source;
        } catch (NoSuchEntityException $e) {
            throw new ValidationException(
                __(
                    'Source with Id %sourceId does not exist for column qty, row number %rowNumber',
                    [
                        'sourceId' => $sourceId,
                        'rowNumber' => $rowNumber
                    ]
                )
            );
        }
    }
}

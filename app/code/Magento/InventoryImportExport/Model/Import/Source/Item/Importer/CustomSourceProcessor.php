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
use Magento\Inventory\Model\SourceItemFactory;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

class CustomSourceProcessor
{
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
     */
    public function __construct(
        SourceItemFactory $sourceItemFactory,
        SourceRepositoryInterface $sourceRepositoryInterface
    ) {
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceRepositoryInterface = $sourceRepositoryInterface;
    }

    /**
     * Execute method for Custom Source Import Processor, creates source item using factory
     *
     * @param array $data
     * @param string|int $rowNumber
     * @return SourceItemInterface|bool
     */
    public function execute(array $data, $rowNumber)
    {
        /** @var SourceInterface $source */
        if ($source = $this->getSource($data['qty'], $rowNumber)) {
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSku($data[Product::COL_SKU]);
            $sourceItem->setSourceId($source->getSourceId());
            $sourceItem->setQuantity($this->getQty($data['qty']));
            $sourceItem->setStatus($this->getIsInStockValue($data, $rowNumber));
            return $sourceItem;
        }
        return false;
    }

    /**
     * Return Source ID if source exists with given id
     *
     * @param $qty
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

    /**
     * Return qty from value passed
     *
     * @param $qty
     * @return int|string
     */
    private function getQty($qty)
    {
        $parts = explode('=', $qty);
        if (is_numeric($parts[1])) {
            return $parts[1];
        }
        return 0;
    }

    /**
     * Work out is_in_stock value it can be in different formats, with an '=' between source id and value
     *
     * @param array $data
     * @param int|string $rowNumber
     * @return int
     */
    private function getIsInStockValue(array $data, $rowNumber)
    {
        $inStock = 0;
        if (isset($data['is_in_stock'])) {
            $stockValue = $data['is_in_stock'];
            if (strpos($stockValue, '=') !== false) {
                $parts = explode('=', $stockValue);
                $inStock = $parts[1];
            } else {
                $inStock = $stockValue;
            }
        }
        return $inStock;
    }
}

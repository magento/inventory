<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\OptionSource\SourceItemStatus;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Check that if product quantity <= 0 status is not "In Stock".
 */
class StatusConsistencyValidator implements SourceItemValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var SourceItemStatus
     */
    private $sourceItemStatus;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param SourceItemStatus $sourceItemStatus
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        SourceItemStatus $sourceItemStatus,
        ProductRepositoryInterface $productRepository,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->sourceItemStatus = $sourceItemStatus;
        $this->productRepository = $productRepository;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $source): ValidationResult
    {
        $product = $this->productRepository->get($source->getSku());
        $typeId = $product->getTypeId() ?: $product->getTypeInstance()->getTypeId();
        $isQty = $this->stockConfiguration->isQty($typeId);
        $quantity = $source->getQuantity();
        $status = $source->getStatus();
        $errors = [];
        if ($this->stockConfiguration->getManageStock()
            && $isQty
            && is_numeric($quantity)
            && (float)$quantity <= 0
            && (int)$status === SourceItemInterface::STATUS_IN_STOCK
        ) {
            $statusOptions = $this->sourceItemStatus->toOptionArray();
            $labels = array_column($statusOptions, 'label', 'value');
            $errors[] = __(
                'Product cannot have "%status" "%in_stock" while product "%quantity" equals or below zero',
                [
                    'status' => SourceItemInterface::STATUS,
                    'in_stock' => $labels[SourceItemInterface::STATUS_IN_STOCK],
                    'quantity' => SourceItemInterface::QUANTITY,

                ]
            );
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}

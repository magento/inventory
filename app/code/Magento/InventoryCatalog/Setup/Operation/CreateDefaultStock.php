<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Operation;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Create default stock during installation
 */
class CreateDefaultStock
{
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var StockInterfaceFactory
     */
    private $stockFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockInterfaceFactory $stockFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param StockRepositoryInterface $stockRepository
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider,
        StockInterfaceFactory $stockFactory,
        DataObjectHelper $dataObjectHelper,
        StockRepositoryInterface $stockRepository
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->stockFactory = $stockFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->stockRepository = $stockRepository;
    }

    /**
     * Create default stock
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    public function execute(): void
    {
        $data = [
            StockInterface::STOCK_ID => $this->defaultStockProvider->getId(),
            StockInterface::NAME => 'Default Stock'
        ];
        $source = $this->stockFactory->create();
        $this->dataObjectHelper->populateWithArray($source, $data, StockInterface::class);
        $this->stockRepository->save($source);
    }
}

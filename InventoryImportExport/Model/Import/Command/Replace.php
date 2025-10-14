<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Command;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryImportExport\Model\Import\SourceItemConvert;

/**
 * @inheritdoc
 */
class Replace implements CommandInterface
{
    /**
     * @var SourceItemConvert
     */
    private $sourceItemConvert;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @param SourceItemConvert $sourceItemConvert
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemsDeleteInterface $sourceItemsDelete
     */
    public function __construct(
        SourceItemConvert $sourceItemConvert,
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemsDeleteInterface $sourceItemsDelete
    ) {
        $this->sourceItemConvert = $sourceItemConvert;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemsDelete = $sourceItemsDelete;
    }

    /**
     * @inheritdoc
     *
     * If an SKU and SOURCE_CODE in the import data matches the SKU and SOURCE_CODE of an existing entity,
     * all fields are deleted and new a record is created.
     *
     * @param array $bunch
     */
    public function execute(array $bunch)
    {
        $sourceItems = $this->sourceItemConvert->convert($bunch);
        $this->sourceItemsDelete->execute($sourceItems);
        $this->sourceItemsSave->execute($sourceItems);
    }
}

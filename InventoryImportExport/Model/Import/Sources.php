<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\ImportExport\Helper\Data as DataHelper;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper as ResourceHelper;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magento\InventoryImportExport\Model\Import\Command\CommandInterface;
use Magento\InventoryImportExport\Model\Import\Serializer\Json;
use Magento\InventoryImportExport\Model\Import\Validator\ValidatorInterface;

/**
 * @inheritdoc
 */
class Sources extends AbstractEntity
{
    /**
     * Column names for import file
     */
    public const COL_SKU = SourceItemInterface::SKU;
    public const COL_SOURCE_CODE = SourceItemInterface::SOURCE_CODE;
    public const COL_QTY = SourceItemInterface::QUANTITY;
    public const COL_STATUS = SourceItemInterface::STATUS;

    /**
     * @var Json
     */
    protected $jsonHelper;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var CommandInterface[]
     */
    private $commands = [];

    /**
     * @var GetSourceCodesBySkusInterface
     */
    private $getSourceCodesBySkus;

    /**
     * @param Json $jsonHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param ResourceHelper $resourceHelper
     * @param DataHelper $dataHelper
     * @param ImportData $importData
     * @param ValidatorInterface $validator
     * @param CommandInterface[] $commands
     * @param GetSourceCodesBySkusInterface|null $getSourceCodesBySkus
     * @throws LocalizedException
     */
    public function __construct(
        Json $jsonHelper,
        ProcessingErrorAggregatorInterface $errorAggregator,
        ResourceHelper $resourceHelper,
        DataHelper $dataHelper,
        ImportData $importData,
        ValidatorInterface $validator,
        array $commands = [],
        GetSourceCodesBySkusInterface $getSourceCodesBySkus = null,
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->errorAggregator = $errorAggregator;
        $this->_resourceHelper = $resourceHelper;
        $this->_importExportData = $dataHelper;
        $this->_dataSourceModel = $importData;
        $this->validator = $validator;

        foreach ($commands as $command) {
            if (!$command instanceof CommandInterface) {
                throw new LocalizedException(
                    __('Source Import Commands must implement %interface.', ['interface' => CommandInterface::class])
                );
            }
        }
        $this->commands = $commands;
        $this->getSourceCodesBySkus = $getSourceCodesBySkus ?: ObjectManager::getInstance()
            ->get(\Magento\InventoryApi\Model\GetSourceCodesBySkusInterface::class);
    }

    /**
     * Import data rows.
     *
     * @return boolean
     * @throws LocalizedException
     */
    protected function _importData()
    {
        $command = $this->getCommandByBehavior($this->getBehavior());

        while ($bunch = $this->_dataSourceModel->getNextUniqueBunch($this->getIds())) {
            foreach ($bunch as $rowData) {
                if ($this->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND) {
                    $sourceCodes = $this->getSourceCodesBySkus->execute([$rowData['sku']]);
                    if (in_array($rowData['source_code'], $sourceCodes)) {
                        $this->countItemsUpdated ++;
                    } else {
                        $this->countItemsCreated ++;
                    }
                } elseif ($this->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE) {
                    $this->countItemsUpdated ++;
                } elseif ($this->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
                    $this->countItemsDeleted ++;
                }
            }
            $command->execute($bunch);
        }

        return true;
    }

    /**
     * To fetch command by behavior
     *
     * @param string $behavior
     * @return CommandInterface
     * @throws LocalizedException
     */
    private function getCommandByBehavior($behavior)
    {
        if (!isset($this->commands[$behavior])) {
            throw new LocalizedException(
                __('There is no command registered for behavior "%behavior".', ['behavior' => $behavior])
            );
        }

        return $this->commands[$behavior];
    }

    /**
     * EAV entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'stock_sources';
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     */
    public function validateRow(array $rowData, $rowNum)
    {
        $result = $this->validator->validate($rowData, $rowNum);
        if ($result->isValid()) {
            return true;
        }

        foreach ($result->getErrors() as $error) {
            $this->addRowError($error, $rowNum);
        }

        return false;
    }
}

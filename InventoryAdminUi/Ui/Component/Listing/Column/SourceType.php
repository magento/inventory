<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\InventoryAdminUi\Model\OptionSource\TypeSource;
use Magento\Inventory\Model\ResourceModel\GetSourceTypeNameBySourceType;

/**
 * Render source type on sources grid.
 */
class SourceType extends Column
{
    /**
     * @var TypeSource
     */
    private $typeSource;

    /**
     * @var GetSourceTypeNameBySourceType
     */
    private $getSourceTypeNameBySourceType;

    /**
     * SourceType constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TypeSource $typeSource
     * @param GetSourceTypeNameBySourceType $getSourceTypeNameBySourceType
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TypeSource $typeSource,
        GetSourceTypeNameBySourceType $getSourceTypeNameBySourceType,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->typeSource = $typeSource;
        $this->getSourceTypeNameBySourceType = $getSourceTypeNameBySourceType;
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['totalRecords'])) {
            return $dataSource;
        }

        if ((int)$dataSource['data']['totalRecords'] === 0) {
            return $dataSource;
        }

        return $this->normalizeData($dataSource);
    }

    /**
     * Normalize Source Data
     *
     * @param $dataSource
     * @return array
     */
    private function normalizeData($dataSource): array
    {
        foreach ($dataSource['data']['items'] as &$item) {
            $sourceType = $item['extension_attributes']['type_code'];
            $item['type_code'] = $this->getSourceTypeNameBySourceType->execute($sourceType);
        }

        return $dataSource;
    }
}

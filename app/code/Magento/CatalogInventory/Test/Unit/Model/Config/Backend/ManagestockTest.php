<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogInventory\Test\Unit\Model\Config\Backend;

class ManagestockTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\CatalogInventory\Model\Config\Backend\Managestock */
    protected $model;

    protected function setUp()
    {
        $this->model = (new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this))->getObject(
            \Magento\CatalogInventory\Model\Config\Backend\Managestock::class,
            [
                'stockIndexerProcessor' => null,
            ]
        );
    }

    /**
     * Data provider for testSaveAndRebuildIndex
     * @return array
     */
    public function saveAndRebuildIndexDataProvider()
    {
        return [
            [1, 1],
            [0, 0],
        ];
    }

    /**
     * @dataProvider saveAndRebuildIndexDataProvider
     *
     * @param int $newStockValue new value for stock status
     * @param int $callCount count matcher
     */
    public function testSaveAndRebuildIndex($newStockValue, $callCount)
    {
        $this->model->setValue($newStockValue);
        $this->model->afterSave();
    }
}

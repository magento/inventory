<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogImportExport\Model\Indexer\Stock\Plugin;

/**
 * @deprecated
 */
class Import
{
    /**
     * @deprecated
     */
    protected $_stockndexerProcessor;

    /**
     * @param $stockndexerProcessor
     */
    public function __construct($stockndexerProcessor)
    {
        $this->_stockndexerProcessor = $stockndexerProcessor;
    }

    /**
     * After import handler
     *
     * @param \Magento\ImportExport\Model\Import $subject
     * @param Object $import
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImportSource(\Magento\ImportExport\Model\Import $subject, $import)
    {
        return $import;
    }
}

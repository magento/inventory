<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\ResourceModel\Indexer;

use Magento\Bundle\Model\ResourceModel\Indexer\OptionQtyExpressionProvider;
use Zend_Db_Expr;

class OptionQtyExpressionProviderPlugin
{
    /**
     * Consider reservations in available quantity calculation for bundle options.
     *
     * @param OptionQtyExpressionProvider $subject
     * @param Zend_Db_Expr $expression
     * @return Zend_Db_Expr
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetExpression(OptionQtyExpressionProvider $subject, Zend_Db_Expr $expression): Zend_Db_Expr
    {
        return new Zend_Db_Expr($expression . ' + IFNULL(reservations.reservation_qty, 0)');
    }
}

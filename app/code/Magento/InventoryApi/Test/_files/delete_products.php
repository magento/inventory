<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

/**
 * Tests which are wrapped with MySQL transaction clear all data by transaction rollback.
 * In that case there is "if" which checks that SKU1, SKU2 and SKU3 still exists in database.
 */
if (!empty($products)) {
    $currentArea = $registry->registry('isSecureArea');
    $registry->unregister('isSecureArea');
    $registry->register('isSecureArea', true);

    foreach ($products as $product) {
        $criteria = $stockStatusCriteriaFactory->create();
        $criteria->setProductsFilter($product->getId());

        $result = $stockStatusRepository->getList($criteria);
        if ($result->getTotalCount()) {
            $stockStatus = current($result->getItems());
            $stockStatusRepository->delete($stockStatus);
        }

        $productRepository->delete($product);
    }

    $registry->unregister('isSecureArea');
    $registry->register('isSecureArea', $currentArea);
}

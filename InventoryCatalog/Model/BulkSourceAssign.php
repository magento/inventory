<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Validation\ValidationException;
use Magento\InventoryCatalogApi\Api\BulkSourceAssignInterface;
use Magento\InventoryCatalogApi\Model\BulkSourceAssignValidatorInterface;
use Magento\InventoryCatalog\Model\ResourceModel\BulkSourceAssign as BulkSourceAssignResource;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemReindexStrategy;

/**
 * @inheritdoc
 */
class BulkSourceAssign implements BulkSourceAssignInterface
{
    /**
     * @var BulkSourceAssignValidatorInterface
     */
    private $assignValidator;

    /**
     * @var BulkSourceAssignResource
     */
    private $bulkSourceAssign;

    /**
     * @var SourceItemReindexStrategy
     */
    private $sourceItemReindexStrategy;

    /**
     * MassProductSourceAssign constructor.
     * @param BulkSourceAssignValidatorInterface $assignValidator
     * @param BulkSourceAssignResource $bulkSourceAssign
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        BulkSourceAssignValidatorInterface $assignValidator,
        BulkSourceAssignResource $bulkSourceAssign,
        SourceItemReindexStrategy $sourceItemReindexStrategy
    ) {
        $this->assignValidator = $assignValidator;
        $this->bulkSourceAssign = $bulkSourceAssign;
        $this->sourceItemReindexStrategy = $sourceItemReindexStrategy;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus, array $sourceCodes): int
    {
        $validationResult = $this->assignValidator->validate($skus, $sourceCodes);
        if (!$validationResult->isValid()) {
            throw new ValidationException(__('Validation Failed'), null, 0, $validationResult);
        }

        $res = $this->bulkSourceAssign->execute($skus, $sourceCodes);
        $this->sourceItemReindexStrategy
            ->getStrategy()
            ->executeList($sourceCodes);

        return $res;
    }
}

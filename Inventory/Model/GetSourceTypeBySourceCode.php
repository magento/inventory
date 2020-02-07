<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\InventoryApi\Model\GetSourceTypeBySourceCodeInterface;

/**
 * @inheritdoc
 */
class GetSourceTypeBySourceCode implements GetSourceTypeBySourceCodeInterface
{

    /**
     * @var ResourceModel\GetSourceTypeBySourceCode
     */
    private $getSourceTypeBySourceCode;

    /**
     * @param ResourceModel\GetSourceTypeBySourceCode $getSourceTypeBySourceCode
     */
    public function __construct(ResourceModel\GetSourceTypeBySourceCode $getSourceTypeBySourceCode)
    {
        $this->getSourceTypeBySourceCode = $getSourceTypeBySourceCode;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $sourceCode): string
    {
        return $this->getSourceTypeBySourceCode->execute($sourceCode);
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Plugin\Inventory\Model;

use Magento\InventoryApi\Api\Data\SourceExtensionInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Model\GetSourceTypeBySourceCodeInterface;

/**
 * Load type to source
 */
class SourceTypeAttributeGet
{

    /**
     * @var GetSourceTypeBySourceCodeInterface
     */
    private $getSourceTypeBySourceCode;

    /**
     * SourceTypeAttribute constructor.
     *
     * @param GetSourceTypeBySourceCodeInterface $getSourceTypeBySourceCode
     */
    public function __construct(
        GetSourceTypeBySourceCodeInterface $getSourceTypeBySourceCode
    ) {
        $this->getSourceTypeBySourceCode = $getSourceTypeBySourceCode;
    }

    /**
     * Load type of source after get source
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface $source
     * @return SourceInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ) {
        $sourceTypeCode = $this->getSourceTypeBySourceCode->execute($source->getSourceCode());

        /** @var SourceExtensionInterface $extension */
        $extension = $source->getExtensionAttributes();
        $extension->setTypeCode($sourceTypeCode);
        $source->setExtensionAttributes($extension);

        return $source;
    }
}

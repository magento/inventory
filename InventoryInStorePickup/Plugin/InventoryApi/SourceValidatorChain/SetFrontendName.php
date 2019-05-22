<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceValidatorChain;

use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Model\SourceValidatorChain;

/**
 * Set frontend name the same as regular name if it is empty
 */
class SetFrontendName
{
    /**
     * @param SourceValidatorChain $chain
     * @param SourceInterface $source
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeValidate(SourceValidatorChain $chain, SourceInterface $source)
    {
        if ($source->getExtensionAttributes() === null) {
            return;
        }

        $frontendName = trim($source->getExtensionAttributes()->getFrontendName() ?? '');

        if ($frontendName === '') {
            $source->getExtensionAttributes()->setFrontendName($source->getName());
            if ($source instanceof DataObject) {
                $source->setData('frontend_name', $source->getExtensionAttributes()->getFrontendName());
            }
        }
    }
}

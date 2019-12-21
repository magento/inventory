<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryApi\Api\Data\SourceExtensionFactory;
use Magento\InventoryApi\Api\Data\SourceExtensionInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Save the type of source after submit form and save source
 */
class PopulateSourceWithData implements ObserverInterface
{
    /**
     * @var SourceExtensionFactory
     */
    protected $extensionFactory;

    /**
     * PopulateSourceWithData constructor.
     * @param SourceExtensionFactory $extensionFactory
     */
    public function __construct(
        SourceExtensionFactory $extensionFactory
    ) {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $params = $observer->getEvent()->getRequest()->getParams();
        $type_code = $params['general']['type_code'];

        /** @var SourceInterface $source */
        $source = $observer->getEvent()->getSource();

        /** @var SourceExtensionInterface $extensionAttributes */
        $extensionAttributes = $source->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
        $extensionAttributes->setTypeCode($type_code);
        $source->setExtensionAttributes($extensionAttributes);
    }
}

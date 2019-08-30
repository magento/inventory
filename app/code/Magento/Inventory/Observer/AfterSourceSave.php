<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Inventory\Model\SourceTypeLinkManagement;

class AfterSourceSave implements ObserverInterface
{
    /**
     * @var SourceTypeLinkManagement
     */
    protected $sourceTypeLinkManagement;

    /**
     * AfterSourceSave constructor.
     * @param SourceTypeLinkManagement $sourceTypeLinkManagement
     */
    public function __construct(SourceTypeLinkManagement $sourceTypeLinkManagement)
    {
        $this->sourceTypeLinkManagement = $sourceTypeLinkManagement;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $params = $observer->getEvent()->getRequest()->getParams();
        $source = $observer->getEvent()->getSource();
        $type_code = $params['general']['type_code'];

//        $extensionAttributes = $source->getExtensionAttributes();
//        $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();

        $this->sourceTypeLinkManagement->saveTypeLinksBySource($source, $type_code);
    }
}
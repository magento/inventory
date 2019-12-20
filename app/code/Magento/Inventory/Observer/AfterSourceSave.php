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

/**
 * Plugin to save type of source after save source
 */
class AfterSourceSave implements ObserverInterface
{
    /**
     * @var SourceTypeLinkManagement
     */
    protected $sourceTypeLinkManagement;

    /**
     * AfterSourceSave constructor.
     *
     * @param SourceTypeLinkManagement $sourceTypeLinkManagement
     */
    public function __construct(SourceTypeLinkManagement $sourceTypeLinkManagement)
    {
        $this->sourceTypeLinkManagement = $sourceTypeLinkManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $source = $observer->getEvent()->getSource();

        $this->sourceTypeLinkManagement->saveTypeLinksBySource($source);
    }
}

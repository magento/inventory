<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * @inheritdoc
 */
class IsSingleSourceModeCache implements IsSingleSourceModeInterface, ResetAfterRequestInterface
{
    /**
     * @var IsSingleSourceMode
     */
    private $isSingleSourceMode;

    /**
     * @var bool
     */
    private $cacheValue;

    /**
     * @param IsSingleSourceMode $isSingleSourceMode
     */
    public function __construct(IsSingleSourceMode $isSingleSourceMode)
    {
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        $this->cacheValue = null;
    }

    /**
     * @inheritdoc
     */
    public function execute(): bool
    {
        if ($this->cacheValue === null) {
            $this->cacheValue = $this->isSingleSourceMode->execute();
        }

        return $this->cacheValue;
    }
}

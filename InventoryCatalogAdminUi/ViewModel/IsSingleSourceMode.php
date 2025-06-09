<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Is single source mode view model.
 */
class IsSingleSourceMode implements ArgumentInterface
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $singleSourceMode;

    /**
     * @param IsSingleSourceModeInterface $singleSourceMode
     */
    public function __construct(IsSingleSourceModeInterface $singleSourceMode)
    {
        $this->singleSourceMode = $singleSourceMode;
    }

    /**
     * Check, if any active additional sources are presented.
     *
     * @return bool
     */
    public function execute(): bool
    {
        return $this->singleSourceMode->execute();
    }
}

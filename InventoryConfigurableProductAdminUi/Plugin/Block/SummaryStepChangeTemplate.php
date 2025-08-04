<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductAdminUi\Plugin\Block;

use Magento\ConfigurableProduct\Block\Adminhtml\Product\Steps\Summary;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Change template if not single store mode.
 */
class SummaryStepChangeTemplate
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var string
     */
    private $multiSourceTemplate;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param string $multiSourceTemplate
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        string $multiSourceTemplate
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->multiSourceTemplate = $multiSourceTemplate;
    }

    /**
     * Changes the template to a multi-source template if the single-source mode is not enabled.
     *
     * @param Summary $bulk
     * @param string $template
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSetTemplate(Summary $bulk, string $template)
    {
        if ($this->isSingleSourceMode->execute() === false) {
            $template = $this->multiSourceTemplate;
        }

        return $template;
    }
}

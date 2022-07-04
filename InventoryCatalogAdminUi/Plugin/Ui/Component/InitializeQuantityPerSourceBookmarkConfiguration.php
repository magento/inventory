<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Plugin\Ui\Component;

use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\Ui\Component\Bookmark;

/**
 * Initializes quantity_per_source bookmark configuration based on qty configuration
 */
class InitializeQuantityPerSourceBookmarkConfiguration
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Initializes "quantity_per_source" bookmark with "qty" bookmark configuration if multiple sources are configured
     *
     * @param Bookmark $bookmark
     * @param array $config
     * @return array
     */
    public function afterGetConfiguration(
        Bookmark $bookmark,
        array $config
    ): array {
        if ($bookmark->getContext()->getNamespace() === 'product_listing' && !$this->isSingleSourceMode->execute()) {
            foreach ($config as $key => $view) {
                if ($key === 'current') {
                    $config[$key] = $this->modifyView($view);
                }
            }
        }

        return $config;
    }

    /**
     * Initializes "quantity_per_source" bookmark with "qty" bookmark configuration
     *
     * @param array $view
     * @return array
     */
    private function modifyView(array $view): array
    {
        $configs = ['positions', 'columns'];
        foreach ($configs as $config) {
            if (!isset($view[$config]['quantity_per_source']) && isset($view[$config]['qty'])) {
                $view[$config]['quantity_per_source'] = $view[$config]['qty'];
            }
        }
        return $view;
    }
}

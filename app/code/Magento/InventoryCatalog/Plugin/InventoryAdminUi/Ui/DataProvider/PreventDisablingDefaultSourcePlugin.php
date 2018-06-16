<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryAdminUi\Ui\DataProvider;

use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider;

class PreventDisablingDefaultSourcePlugin
{
    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @param SourceDataProvider $subject
     * @param $meta
     * @return array
     */
    public function afterGetMeta(
        SourceDataProvider $subject,
        $meta
    ): array {
        $data = $subject->getData();
        $defaultSourceCode = $this->defaultSourceProvider->getCode();
        if (array_key_exists($defaultSourceCode, $data)) {
            //$result[$defaultSourceCode]['general']['switcher_disabled'] = true;
            $meta['general'] = [
                'children' => [
                    'enabled' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => true,
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        return $meta;
    }
}

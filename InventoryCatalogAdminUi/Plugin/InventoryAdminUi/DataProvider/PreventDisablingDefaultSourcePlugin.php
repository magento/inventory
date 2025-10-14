<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Plugin\InventoryAdminUi\DataProvider;

use Magento\Framework\App\RequestInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider;

class PreventDisablingDefaultSourcePlugin
{
    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param RequestInterface $request
     */
    public function __construct(
        DefaultSourceProviderInterface $defaultSourceProvider,
        RequestInterface $request
    ) {
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->request = $request;
    }

    /**
     * Modifies the meta configuration to disable the "enabled" field if the source is the default source.
     *
     * @param SourceDataProvider $subject
     * @param array $meta
     * @return array
     */
    public function afterGetMeta(
        SourceDataProvider $subject,
        array $meta
    ): array {
        $isFormComponent = SourceDataProvider::SOURCE_FORM_NAME === $subject->getName();
        if (!$isFormComponent || !$this->isDefaultSource()) {
            return $meta;
        }

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

        return $meta;
    }

    /**
     * Checks if the current source code matches the default source code provided by the default source provider.
     *
     * @return bool
     */
    private function isDefaultSource(): bool
    {
        $defaultSourceCode = $this->defaultSourceProvider->getCode();
        $currentSourceCode = $this->request->getParam(SourceItemInterface::SOURCE_CODE);
        return $defaultSourceCode === $currentSourceCode;
    }
}

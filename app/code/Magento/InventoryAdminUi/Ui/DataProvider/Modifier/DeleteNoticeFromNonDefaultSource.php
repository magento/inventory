<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\DataProvider\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\RequestInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;

/**
 * Remove notice from enable field for non-default sources
 */
class DeleteNoticeFromNonDefaultSource extends AbstractModifier
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @param RequestInterface $request
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        RequestInterface $request,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->request = $request;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @inheritdoc
     * @param array $data
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $sourceCode = $this->request->getParam(SourceInterface::SOURCE_CODE);
        if ($sourceCode !== $this->defaultSourceProvider->getCode()) {
            $meta['general']['children']['enabled'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'notice' => null,
                        ]
                    ]
                ]
            ];
        }

        return $meta;
    }
}

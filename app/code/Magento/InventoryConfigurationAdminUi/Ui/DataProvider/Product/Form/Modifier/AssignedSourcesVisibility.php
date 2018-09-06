<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

class AssignedSourcesVisibility extends AbstractModifier
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(ArrayManager $arrayManager)
    {
        $this->arrayManager = $arrayManager;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $meta = $this->changeVisibility($meta, 'assign_sources_button');

        return $this->changeVisibility($meta, 'assigned_sources');
    }

    /**
     * @param array $meta
     * @param string $path
     * @return array
     */
    private function changeVisibility(array $meta, string $path): array
    {
        $path = $this->arrayManager->findPath(
            $path,
            $meta,
            null,
            'children'
        );

        if (null === $path) {
            return $meta;
        }

        $meta = $this->arrayManager->set(
            $path . '/arguments/data/config',
            $meta,
            [
                'visible' => 1,
                'imports' => '',
            ]
        );

        return $meta;
    }
}

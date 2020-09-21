<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\Component\Listing;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Source Columns component.
 */
class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param ContextInterface $context
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        $this->authorization = $authorization;
        parent::__construct($context, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        parent::prepare();

        $config = $this->getConfiguration();
        if (!empty($config['editorConfig'])) {
            $config['editorConfig']['enabled'] =
                (bool)$this->authorization->isAllowed('Magento_InventoryApi::source_edit');
        }

        $origConfig = $this->getConfiguration();
        if ($origConfig !== $config) {
            $config = array_replace_recursive($origConfig, $config);
        }

        $this->setData('config', $config);
    }
}

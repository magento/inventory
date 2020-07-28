<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\Component\Listing\Stock;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Mass action stock listing UI component.
 */
class MassAction extends \Magento\Ui\Component\MassAction
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
        parent::__construct($context, $components, $data);
        $this->authorization = $authorization;
    }

    /**
     * @inheritdoc
     */
    public function prepare() : void
    {
        foreach ($this->getChildComponents() as $actionComponent) {
            $componentConfig = $actionComponent->getConfiguration();
            $actionType = $componentConfig['type'];
            $componentConfig['actionDisable'] = !$this->isActionAllowed($actionType);
            $actionComponent->setData('config', $componentConfig);
        }

        parent::prepare();
    }

    /**
     * Check if the given type of action is allowed
     *
     * @param string $actionType
     * @return bool
     */
    private function isActionAllowed(string $actionType): bool
    {
        $isAllowed = true;
        switch ($actionType) {
            case 'delete':
                $isAllowed = (bool)$this->authorization->isAllowed('Magento_InventoryApi::stock_delete');
                break;
            default:
                break;
        }

        return $isAllowed;
    }
}

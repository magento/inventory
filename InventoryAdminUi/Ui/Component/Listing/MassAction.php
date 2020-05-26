<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\Component\Listing;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Source MassActions component.
 */
class MassAction extends \Magento\Ui\Component\MassAction
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param AuthorizationInterface $authorization
     * @param ContextInterface $context
     * @param UiComponentInterface[] $components
     * @param array $data
     */
    public function __construct(
        AuthorizationInterface $authorization,
        ContextInterface $context,
        array $components = [],
        array $data = []
    ) {
        $this->authorization = $authorization;
        parent::__construct($context, $components, $data);
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
            case 'enable':
            case 'disable':
                $isAllowed = (bool)$this->authorization->isAllowed('Magento_InventoryApi::source_edit');
                break;
            default:
                break;
        }

        return $isAllowed;
    }
}

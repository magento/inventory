<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Ui\Component\Listing\Stock\Column;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

/**
 * Represents Edit link in Stock grid for entity by its identifier field
 */
class EditAction extends \Magento\Backend\Ui\Component\Listing\Column\EditAction
{
    /**
     * @var AuthorizationInterface
     */
    private $authorizationObject;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     * @param AuthorizationInterface|null $authorizationObject
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [],
        ?AuthorizationInterface $authorizationObject = null
    ) {
        $this->authorizationObject = $authorizationObject ?? ObjectManager::getInstance()
                ->get(AuthorizationInterface::class);
        parent::__construct($context, $uiComponentFactory, $urlBuilder, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $sourceData)
    {
        $sourceData = parent::prepareDataSource($sourceData);
        $actionsTitle = $this->getData('name');

        if (isset($sourceData['data']['items'])) {
            foreach ($sourceData['data']['items'] as &$item) {
                if (!empty($item[$actionsTitle]['edit'])) {
                    $item[$actionsTitle]['edit']['hidden'] =
                        !$this->authorizationObject->isAllowed('Magento_InventoryApi::stock_edit');
                }
            }
        }

        return $sourceData;
    }
}

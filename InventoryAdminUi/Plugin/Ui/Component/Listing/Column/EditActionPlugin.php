<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Plugin\Ui\Component\Listing\Column;

use Magento\Backend\Ui\Component\Listing\Column\EditAction;
use Magento\Framework\AuthorizationInterface;

/**
 * Hide Edit link depending on Acl Resource.
 */
class EditActionPlugin
{
    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        AuthorizationInterface $authorization
    ) {
        $this->authorization = $authorization;
    }

    /**
     * Hide Edit link depending on Acl Resource.
     *
     * @param EditAction $subject
     * @param array $dataSource
     * @return array
     */
    public function afterPrepareDataSource(
        EditAction $subject,
        array $dataSource
    ) {
        $actionsName = $subject->getData('name');
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!empty($item[$actionsName]['edit'])) {
                    $item[$actionsName]['edit']['hidden'] =
                        !$this->authorization->isAllowed('Magento_InventoryApi::source_edit');
                }
            }
        }

        return $dataSource;
    }
}

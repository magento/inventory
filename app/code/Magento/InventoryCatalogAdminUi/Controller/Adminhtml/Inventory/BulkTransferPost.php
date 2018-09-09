<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Controller\Adminhtml\Inventory;

use Magento\AsynchronousOperations\Model\MassSchedule;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth;
use Magento\Framework\Controller\ResultFactory;
use Magento\InventoryCatalogAdminUi\Model\BulkSessionProductsStorage;

class BulkTransferPost extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var BulkSessionProductsStorage
     */
    private $bulkSessionProductsStorage;

    /**
     * @var MassSchedule
     */
    private $massSchedule;

    /**
     * @var Auth
     */
    private $authSession;

    /**
     * @param Action\Context $context
     * @param BulkSessionProductsStorage $bulkSessionProductsStorage
     * @param MassSchedule $massSchedule
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        Action\Context $context,
        BulkSessionProductsStorage $bulkSessionProductsStorage,
        MassSchedule $massSchedule
    ) {
        parent::__construct($context);

        $this->bulkSessionProductsStorage = $bulkSessionProductsStorage;
        $this->massSchedule = $massSchedule;
        $this->authSession = $context->getAuth();
    }

    /**
     * @param array $skus
     * @param string $originSource
     * @param string $destinationSource
     * @param bool $unassignSource
     * @return array
     */
    private function createEntities(
        array $skus,
        string $originSource,
        string $destinationSource,
        bool $unassignSource
    ): array {
        $entities = [];

        foreach ($skus as $sku) {
            $entities[] = [
                'sku' => $sku,
                'originSource' => $originSource,
                'destinationSource' => $destinationSource,
                'unassignFromOrigin' => $unassignSource,
            ];
        }

        return $entities;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\BulkException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $originSource = $this->getRequest()->getParam('origin_source', '');
        $destinationSource = $this->getRequest()->getParam('destination_source', '');

        $userId = (int) $this->authSession->getUser()->getId();

        $skus = $this->bulkSessionProductsStorage->getProductsSkus();
        $unassignSource = (bool) $this->getRequest()->getParam('unassign_origin_source', false);

        $entities = $this->createEntities($skus, $originSource, $destinationSource, $unassignSource);
        $this->massSchedule->publishMass(
            'async.V1.inventory.product-source-transfer.POST',
            $entities,
            null,
            $userId
        );

        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $result->setPath('catalog/product/index');
    }
}

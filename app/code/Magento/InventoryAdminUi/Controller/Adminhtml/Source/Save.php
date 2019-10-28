<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Controller\Adminhtml\Source;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryAdminUi\Model\Source\SourceHydrator;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Source save controller.
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_InventoryApi::source';

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceFactory;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SourceHydrator
     */
    private $sourceHydrator;

    /**
     * @param Context $context
     * @param SourceInterfaceFactory $sourceFactory
     * @param SourceRepositoryInterface $sourceRepository
     * @param SourceHydrator $sourceHydrator
     */
    public function __construct(
        Context $context,
        SourceInterfaceFactory $sourceFactory,
        SourceRepositoryInterface $sourceRepository,
        SourceHydrator $sourceHydrator
    ) {
        parent::__construct($context);
        $this->sourceFactory = $sourceFactory;
        $this->sourceRepository = $sourceRepository;
        $this->sourceHydrator = $sourceHydrator;
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();
        $requestData = $request->getPost()->toArray();

        if (!$request->isPost() || empty($requestData['general'])) {
            $this->messageManager->addErrorMessage(__('Wrong request.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
            return $resultRedirect;
        }
        $sourceCode = $requestData['general'][SourceInterface::SOURCE_CODE];
        try {
            $source = $this->sourceRepository->get($sourceCode);
            if ($source->getPostcode() !== $requestData['general'][SourceInterface::POSTCODE]) {
                unset($requestData['general'][SourceInterface::LATITUDE]);
                unset($requestData['general'][SourceInterface::LONGITUDE]);
                $source->setLatitude(null);
                $source->setLongitude(null);
            }
        } catch (NoSuchEntityException $e) {
            $source = $this->sourceFactory->create();
        }
        try {
            $this->processSave($source, $requestData);
            $this->messageManager->addSuccessMessage(__('The Source has been saved.'));
            $this->processRedirectAfterSuccessSave($resultRedirect, $source->getSourceCode());
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $localizedError) {
                $this->messageManager->addErrorMessage($localizedError->getMessage());
            }
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave($resultRedirect, $sourceCode);
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave($resultRedirect, $sourceCode);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save Source.'));
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave($resultRedirect, $sourceCode);
        }
        return $resultRedirect;
    }

    /**
     * Hydrate data from request and save source.
     *
     * @param SourceInterface $source
     * @param array $requestData
     * @return void
     * @throws CouldNotSaveException
     * @throws ValidationException
     */
    private function processSave(SourceInterface $source, array $requestData)
    {
        $source = $this->sourceHydrator->hydrate($source, $requestData);

        $this->_eventManager->dispatch(
            'controller_action_inventory_populate_source_with_data',
            [
                'request' => $this->getRequest(),
                'source' => $source,
            ]
        );

        $this->sourceRepository->save($source);

        $this->_eventManager->dispatch(
            'controller_action_inventory_source_save_after',
            [
                'request' => $this->getRequest(),
                'source' => $source,
            ]
        );
    }

    /**
     * Get redirect url after source save.
     *
     * @param Redirect $resultRedirect
     * @param string $sourceCode
     * @return void
     */
    private function processRedirectAfterSuccessSave(Redirect $resultRedirect, string $sourceCode)
    {
        if ($this->getRequest()->getParam('back')) {
            $resultRedirect->setPath(
                '*/*/edit',
                [
                    SourceInterface::SOURCE_CODE => $sourceCode,
                    '_current' => true,
                ]
            );
        } elseif ($this->getRequest()->getParam('redirect_to_new')) {
            $resultRedirect->setPath(
                '*/*/new',
                [
                    '_current' => true,
                ]
            );
        } else {
            $resultRedirect->setPath('*/*/');
        }
    }

    /**
     * Get redirect url after unsuccessful source save.
     *
     * @param Redirect $resultRedirect
     * @param string|null $sourceCode
     * @return void
     */
    private function processRedirectAfterFailureSave(Redirect $resultRedirect, string $sourceCode = null)
    {
        if (null === $sourceCode) {
            $resultRedirect->setPath('*/*/new');
        } else {
            $resultRedirect->setPath(
                '*/*/edit',
                [
                    SourceInterface::SOURCE_CODE => $sourceCode,
                    '_current' => true,
                ]
            );
        }
    }
}

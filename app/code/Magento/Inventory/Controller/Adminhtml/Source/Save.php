<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Controller\Adminhtml\Source;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Controller\Adminhtml\Source\Save\ProcessSave;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Save Controller
 */
class Save extends Action
{
    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_InventoryApi::source';

    /**
     * @var ProcessSave
     */
    private $processSave;

    /**
     * @param Context $context
     * @param ProcessSave $processSave
     */
    public function __construct(
        Context $context,
        ProcessSave $processSave
    ) {
        parent::__construct($context);
        $this->processSave = $processSave;
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

        try {
            $source = $this->processSave->execute($request);
            $this->messageManager->addSuccessMessage(__('The Source has been saved.'));
            $this->processRedirectAfterSuccessSave($resultRedirect, $source->getSourceCode());
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The Source does not exist.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
        } catch (ValidationException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave($resultRedirect);
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave(
                $resultRedirect,
                $requestData['general'][SourceInterface::SOURCE_CODE] ?? null
            );
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save Source.'));
            $this->_session->setSourceFormData($requestData);
            $this->processRedirectAfterFailureSave(
                $resultRedirect,
                $requestData['general'][SourceInterface::SOURCE_CODE] ?? null
            );
        }
        return $resultRedirect;
    }

    /**
     * @param Redirect $resultRedirect
     * @param string $sourceCode
     * @return void
     */
    private function processRedirectAfterSuccessSave(Redirect $resultRedirect, string $sourceCode)
    {
        if ($this->getRequest()->getParam('back')) {
            $resultRedirect->setPath('*/*/edit', [
                SourceInterface::SOURCE_CODE => $sourceCode,
                '_current' => true,
            ]);
        } elseif ($this->getRequest()->getParam('redirect_to_new')) {
            $resultRedirect->setPath('*/*/new', [
                '_current' => true,
            ]);
        } else {
            $resultRedirect->setPath('*/*/');
        }
    }

    /**
     * @param Redirect $resultRedirect
     * @param string|null $sourceCode
     * @return void
     */
    private function processRedirectAfterFailureSave(Redirect $resultRedirect, string $sourceCode = null)
    {
        if (null === $sourceCode) {
            $resultRedirect->setPath('*/*/new');
        } else {
            $resultRedirect->setPath('*/*/edit', [
                SourceInterface::SOURCE_CODE => $sourceCode,
                '_current' => true,
            ]);
        }
    }
}

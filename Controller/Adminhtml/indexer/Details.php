<?php
/**
 * Copyright © 2023 EasyMage. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @author    Amit Biswas <amit.biswas.webdev@gmail.com>
 * @copyright 2023 EasyMage
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace EasyMage\IndexerDetails\Controller\Adminhtml\indexer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Indexer\Controller\Adminhtml\Indexer;
use Magento\Backend\Model\Session;
use EasyMage\IndexerDetails\Ui\DataProvider\Product\DataProvider;

/**
 * @ClassName Details
 */
class Details extends Indexer implements HttpGetActionInterface
{
    /**
     * @var Session
     */
    private Session $backendSession;

    /**
     * @var Http
     */
    private Http $request;

    /**
     * @var RedirectFactory
     */
    private RedirectFactory $redirectFactory;

    public function __construct(
        Http $request,
        Session $backendSession,
        RedirectFactory $redirectFactory,
        Context $context
    ) {
        $this->request = $request;
        $this->backendSession = $backendSession;
        $this->redirectFactory = $redirectFactory;
        parent::__construct($context);
    }

    /**
     * @return ResultInterface|Redirect|void
     */
    public function execute()
    {
        $indexType = $this->request->getParam('type');
        if ($this->canShowPage($indexType)) {
            $this->backendSession->setIndexerType($indexType);
            $this->_view->loadLayout();
            $this->_setActiveMenu('Magento_Indexer::system_index');
            $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Index Management Detail Information'));
            $this->_view->renderLayout();
        } else {
            $this->messageManager->addErrorMessage(__('Something went wrong. Please try again.'));
            return $this->redirectFactory->create()->setPath('indexer/indexer/list');
        }
    }

    /**
     * Check Grid List Permission.
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Indexer::index');
    }

    /**
     * Check whether page can be shown
     *
     * @return bool
     */
    protected function canShowPage($indexType): bool
    {
        return $indexType !== '' && in_array($indexType, DataProvider::ALLOWED_VIEW_DETAILS, true);
    }
}

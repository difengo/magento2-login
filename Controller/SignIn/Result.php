<?php

namespace Difengo\Login\Controller\SignIn;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Element\Messages;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class Result extends Action
{
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @type \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @type \Magento\Customer\Api\AccountManagementInterface
     */
    protected $accountManager;


    /** @var PageFactory $resultPageFactory */
    protected $resultPageFactory;

    /**
     * Result constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Context $context, 
        PageFactory $pageFactory,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement, 
        LoggerInterface $logger)
    {
        $this->resultPageFactory = $pageFactory;
        $this->logger = $logger;
        $this->session = $customerSession;
        $this->accountManager = $customerAccountManagement;

        $this->logger->addInfo('Result controller was instanciated.');

        parent::__construct($context);
    }

    /**
     * The controller action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $token = $this->getRequest()->getParam('difengo_token');

        $this->logger->addInfo('Passed token was: ' . $token);

        $difengo_id = '002345678';

        $customerCollection = $this->getCustomerCollection($difengo_id);

        if($customerCollection->getSize())
        {
            $customer = $collection->getFirstItem();

            $this->logger->addInfo('Customer ' . $item->getId() . 'was retrieved.');

            $this->session->setCustomerDataAsLoggedIn($customer);
            $this->session->regenerateId();
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        $request = $this->getRequest()->getParams();           
        $this->resultPageFactory->create();

        return $resultRedirect->setPath('');
    }

    protected function getCustomerCollection($ext_id) 
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerCollection = $objectManager->create('Magento\Customer\Model\ResourceModel\Customer\Collection');
         /** Applying Filters */
        $customerCollection
            ->addAttributeToSelect(array('id','firstname', 'ext_id',  'email'))
            ->addAttributeToFilter('ext_id', array('eq' => $ext_id));

        return $customerCollection->load();
    }
    
    
}
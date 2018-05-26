<?php
 
namespace Difengo\Login\Controller\SignIn;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
 
class Index extends Action
{
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * PageFactory
     *
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LoggerInterface $logger
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;

        parent::__construct($context);
    }
 
    public function execute()
    {
        $this->logger->addInfo('Sign In Controller was called.');

        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}
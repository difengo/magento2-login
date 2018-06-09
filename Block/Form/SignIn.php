<?php

namespace Difengo\Login\Block\Form;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Block\Form\Login;
use Psr\Log\LoggerInterface;

class SignIn extends Template
{

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;

        $this->logger->addInfo('Sign In Block was instanciated.');

        parent::__construct($context);
    }

    public function getTitle()
    {
        $this->_logger->addInfo('getTitle was called.');

        return 'Difengo Login';
    }

    public function getFormAction()
    {
        return '/difengo/signin/result';
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        return $this;
    }
}
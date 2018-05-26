<?php

namespace Difengo\Login\Block\Form;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;

class SignIn extends Template
{

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_logger = $logger;

        $this->_logger->addInfo('Sign In Block was instanciated.');

        parent::__construct($context);
    }

    public function getTitle()
    {
        $this->_logger->addInfo('getTitle was called.');

        return 'Difengo Login';
    }
}
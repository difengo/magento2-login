<?php

namespace Difengo\Login\Helper;

use Magento\Framework\App\Helper;
use Magento\Framework\HTTP\Client\Curl;

class ApiClient extends AbstractHelper 
{
    const RECEIPT_POST_ENDPOINT  =	"/api/receipt/save";
    const CUSTOMER_POST_ENDPOINT =	"/api/customer/save";
    const CUSTOMER_GET_ENDPOINT  =	"/api/customer/get";

    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $curl;

    /**
    * @var \Difengo\Login\Helper\Data
    */
    protected $configuration;

    public function __construct(Curl $curl, Data $configuration) 
    {

        $this->curl = $curl;
        $this->configuration = $configuration;
    }

    public function authenticate()
    {
        //TODO: Read Key and Secret from configuration
        //TODO: call remote server to get token
        //TODO: keep token for future requests
    }

    public function getRemoteCustomerData($token)
    {
        //TODO: Validate passed token
        //TODO: use authentication token to post request  
        //$url = urlencode($url);  
        //$this->curl->get($url);
        //$response = $this->curl->getBody();

        //return $response;
    }
}
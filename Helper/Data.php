<?php

namespace Difengo\Login\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Data extends AbstractHelper
{
    const CONFIG_API_URI = 'login/security/api_uri';
    const CONFIG_API_PORT = 'login/security/api_port';
    const CONFIG_API_SECRET = 'login/security/api_secret';
    const CONFIG_API_KEY = 'login/security/api_key';

    public function getApiUri()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::CONFIG_API_URI, $storeScope);
    }

    public function getApiPort()
    {
        return 8080;
    }

    public function getApiSecret()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::CONFIG_API_SECRET, $storeScope);
    }

    public function getApiKey()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::CONFIG_API_KEY, $storeScope);
    }
}
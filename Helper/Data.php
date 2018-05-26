<?php

namespace Difengo\Login\Helper;

use Magento\Framework\App\Helper;

class Data extends AbstractHelper
{
    /**
     * Path to store config if module is enabled
     *
     * @var string
     */
    const CONFIG_ENABLED = 'difengo/login/general/enabled';

    /**
     * Check if module is enabled
     *
     * @return string|null
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
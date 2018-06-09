<?php

namespace Difengo\Login\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

use Difengo\Login\Helper\AttributeSetup;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Logger
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AttributeSetup
     */
    protected $attributeSetup;
        
    /**
     * @param AttributeSetup $attributeSetup
     * @param LoggerInterface $logger
     */
    public function __construct(
        AttributeSetup $attributeSetup,
        LoggerInterface $logger
    ) {
        $this->attributeSetup = $attributeSetup;
        $this->logger = $logger;
    }
 
    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $attributeSetup->installAttributes($setup, $context);
    }
}
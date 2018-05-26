<?php

namespace Difengo\Login\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSet;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

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
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;
        
    /**
     * @param CustomerSetupFactory $customerSetupFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory,
        LoggerInterface $logger
    ) {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->logger = $logger;
    }
 
    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->logger->addInfo('Upgrading Difengo custom attributes...');

        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        $attributeCode = "difengo_id";
        $customAttribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeCode);

        if($customAttribute == null)
        {
            $this->logger->addInfo('Installing Difengo custom attributes...');

            $setup->startSetup();

            $customerSetup->addAttribute('customer',
            $attributeCode, [
                'label' => 'Difengo Id',
                'type' => 'text',
                'frontend_input' => 'text',
                'required' => false,
                'visible' => true,
                'system'=> 0,
                'position' => 105,
                'adminhtml_only'=>1,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
            ]);

            $customAttribute = $customerSetup->getEavConfig()->getAttribute('customer', $attributeCode);
        
            $used_in_forms = array();
            $used_in_forms[]='adminhtml_customer';
            $customAttribute->setData('used_in_forms', $used_in_forms)
                ->setData('is_used_for_customer_segment', true)
                ->setData('is_system', 0)
                ->setData('is_user_defined', 1)
                ->setData('is_visible', 1)
                ->setData('sort_order', 130)
                ->save();

            $setup->endSetup();

            $this->logger->addInfo('Difengo custom attributes installed.');
        }
        else
        {
            $this->logger->addInfo('Difengo custom attributes already installed.');
        }
    }
}
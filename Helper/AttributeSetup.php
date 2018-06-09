<?php

namespace Difengo\Login\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

class AttributeSetup extends AbstractHelper 
{
    const DIFENGO_ID  =	'difengo_id';

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

    public function installAttributes(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->logger->addInfo('Installing Difengo custom attributes...');

        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        $customerAttribute = $customerSetup->getEavConfig()->getAttribute('customer', self::DIFENGO_ID);
        $addressAttribute = $customerSetup->getEavConfig()->getAttribute('customer_address', self::DIFENGO_ID);

        $setup->startSetup();

        if($customerAttribute == null)
        {
            $this->logger->addInfo('Installing Difengo customer attribute...');

            $customerSetup->addAttribute('customer',
            self::DIFENGO_ID, [
                'label' => 'Difengo Id',
                'type' => 'text',
                'frontend_input' => 'text',
                'required' => false,
                'visible' => true,
                'system' => 0,
                'position' => 105,
                'adminhtml_only' => 1,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
            ]);

            $customerAttribute = $customerSetup->getEavConfig()->getAttribute('customer', self::DIFENGO_ID);

            $used_in_forms = array();
            $used_in_forms[]='adminhtml_customer';
            $customerAttribute->setData('used_in_forms', $used_in_forms)
                ->setData('is_used_for_customer_segment', true)
                ->setData('is_system', 0)
                ->setData('is_user_defined', 1)
                ->setData('is_visible', 1)
                ->setData('sort_order', 130)
                ->save();

            $this->logger->addInfo('Difengo customer attribute installed.');
        }

        if($addressAttribute == null)
        {
            $this->logger->addInfo('Installing Difengo customer address attribute...');

            $customerSetup->addAttribute('customer_address',
            self::DIFENGO_ID, [
                'label' => 'Difengo Id',
                'type' => 'text',
                'frontend_input' => 'text',
                'required' => false,
                'visible' => true,
                'system' => 0,
                'position' => 105,
                'adminhtml_only' => 1,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => false,
            ]);

            $addressAttribute = $customerSetup->getEavConfig()->getAttribute('customer_address', self::DIFENGO_ID);
            
            $used_in_forms = array();
            $used_in_forms[]='adminhtml_customer_address';
            $addressAttribute->setData('used_in_forms', $used_in_forms)
                ->setData('is_system', 0)
                ->setData('is_user_defined', 1)
                ->setData('is_visible', 1)
                ->setData('sort_order', 130)
                ->save();
            
            $this->logger->addInfo('Difengo customer address attribute installed.');
        }

        $setup->endSetup();

        $this->logger->addInfo('Difengo custom attributes installed.');
    }
}
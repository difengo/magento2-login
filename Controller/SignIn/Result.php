<?php

namespace Difengo\Login\Controller\SignIn;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Element\Messages;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Api\Data\AddressInterface;

use Difengo\Login\Helper\ApiClient;
use Difengo\Login\Helper\AttributeSetup;

class Result extends Action
{
    const DIFENGO_TOKEN  =	'difengo_token';

    /**
     * @type \Psr\Log\LoggerInterface
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

    /**
     * @type \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @type \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $addressFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * $addressRepository
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @type \Difengo\Login\Helper\ApiClient
     */
    protected $apiClient;

     /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * Result constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Session $customerSession
     * @param ManagerInterface $messageManager
     * @param Validator $formKeyValidator
     * @param AccountManagementInterface $customerAccountManagement
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     * @param CustomerRepository $customerRepository
     * @param LoggerInterface $logger
     * @param ApiClient $apiClient
     */
    public function __construct(
        Context $context, 
        PageFactory $pageFactory,
        Session $customerSession,
        ManagerInterface $messageManager,
        Validator $formKeyValidator,
        AccountManagementInterface $customerAccountManagement, 
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        LoggerInterface $logger,
        ApiClient $apiClient)
    {
        $this->resultPageFactory = $pageFactory;
        $this->logger = $logger;
        $this->session = $customerSession;
        $this->messageManager = $messageManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->accountManager = $customerAccountManagement;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->apiClient = $apiClient;

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
        // We retrieve the passed token
        $token = $this->getRequest()->getParam(self::DIFENGO_TOKEN);

        if($token == null)
            throw new \Exception('Token cannot be null.');

        $this->logger->addInfo('Passed token was: ' . $token);

        $apiCustomer = $this->apiClient->getRemoteCustomerData($token);

        // We test the returned result
        if($apiCustomer == null)
        {
            $this->logger->addInfo('Remote customer was not retrieved.');
        }
        else
        {
            $this->logger->addInfo('Retrieving customer from local store...');

            // We query the local database with the returned id
            $customer = $this->getCustomerByDifengoId($apiCustomer->id);

            if($customer != null)
            {
                $this->logger->addInfo('Customer ' . $customer->getId() . ' was retrieved.');

                $save = false;

                // We update customer information if required
                if($customer->getFirstname() != $apiCustomer->firstName)
                {
                    $customer->setFirstname($apiCustomer->firstName);
                    $save = true;
                }

                if($customer->getLastname() != $apiCustomer->lastName)
                {
                    $customer->setLastname($apiCustomer->lastName);
                    $save = true;
                }

                if($customer->getEmail() != $apiCustomer->email)
                {
                    $customer->setEmail($apiCustomer->email);
                    $save = true;
                }

                //Manage address
                $addresses = $customer->getAddresses();

                if(count($addresses) == 0)
                {
                    $defaultDelivery = false;

                    if(count($apiCustomer->addresses) == 1)
                        $defaultDelivery = true;

                    foreach ($apiCustomer->addresses as $apiAddress)
                    {
                        $this->createAddress($customer, $apiAddress, $defaultDelivery);
                    }
                }
                else
                {
                    $this->logger->addInfo('Some existing addresses were found.');

                    $addressesToCreate = array();

                    foreach($addresses as $address)
                    {
                        $name = $address->getCustomAttribute(AttributeSetup::DIFENGO_ID);

                        $this->logger->addInfo('Existing address: ' . $name);

                        if($name != null)
                        {
                            $inReturnedAddresses = false;

                            foreach ($apiCustomer->addresses as $apiAddress)
                            {
                                if(isset($apiAddress->name))
                                {
                                    if(!array_key_exists($apiAddress->name, $addressesToCreate))
                                        $addressesToCreate[$apiAddress->name] = $apiAddress;

                                    if($apiAddress->name == $name)
                                    {
                                        if(array_key_exists($apiAddress->name, $addressesToCreate))
                                            unset($addressesToCreate[$apiAddress->name]);

                                        $inReturnedAddresses = true;
                                        $this->updateAddress($address, $apiAddress);
                                    }
                                }
                            }
                            
                            if($inReturnedAddresses == false)
                            {
                                $this->deleteAddress($address);
                                $save = true;
                            } 
                        }
                    }

                    foreach ($addressesToCreate as $key => $value)
                    {
                        $this->createAddress($customer, $value, false);
                    }
                }
                
                if($save == true)
                {
                    $customer->cleanAllAddresses();
                    $customer->save();
                    $this->logger->addInfo('Customer ' . $customer->getId() . ' has been updated.');
                }
                    
                // We add the token to the session for it to be used by the invoicing module
                $this->session->setData(self::DIFENGO_TOKEN, $token);
                $this->session->setCustomerDataAsLoggedIn($customer);
                $this->session->regenerateId();
            }
            else
            {
                $this->logger->addInfo('Creating new customer...');

                // We register the customer 
                // Get Website Id and store
                $websiteId  = $this->storeManager->getWebsite()->getWebsiteId();
                $store = $this->$storeManager->getStore();

                // Instantiate object (this is the most important part)
                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($websiteId);

                // Preparing data for new customer
                $customer->setFirstname($apiCustomer->firstName);
                $customer->setLastname($apiCustomer->lastName);
                $customer->setEmail($apiCustomer->email); 
                $customer->setStoreId($store->getId());
                $customer->setCreatedIn($store->getName());
                $customer->setData(AttributeSetup::DIFENGO_ID, $apiCustomer->id);
                $customer->setConfirmation(null);
                
                if(isset($apiCustomer->gender))
                    $customer->setGender($apiCustomer->gender);

                // Save customer
                $customer->save();

                $defaultDelivery = false;

                if(count($apiCustomer->addresses) == 1)
                    $defaultDelivery = true;

                foreach ($apiCustomer->addresses as $apiAddress)
                {
                    $this->createAddress($customer, $apiAddress, $defaultDelivery);
                }
                
                $this->logger->addInfo('New customer was created.');

                $this->session->setData(self::DIFENGO_TOKEN, $token);
                $this->session->setCustomerDataAsLoggedIn($customer);
                $this->session->regenerateId();
            }

            // We redirect to the homepage
            $this->logger->addInfo('Redirecting to homepage...');
            return $this->redirect('');
        }
    }

    private function getCustomerByDifengoId($id) 
    {
        $customerCollection = $this->customerFactory->create()->getCollection();

        $customerCollection
            ->addAttributeToSelect(array('id'))
            ->addAttributeToFilter(AttributeSetup::DIFENGO_ID, array('eq' => $id));

        $resultCollection = $customerCollection->load();

        if($resultCollection != null && $resultCollection->getSize())
        {
            $result = $customerCollection->getFirstItem();

            return $this->getCustomerById($result->getId()); 
        }

        return null;
    }

    private function getCustomerById($id) 
    {
        $this->logger->addInfo('Retrieving customer by id:' . $id);

        return $this->customerRepository->getById($id);
    }

    private function redirect($page)
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $request = $this->getRequest()->getParams();           
        $this->resultPageFactory->create();

        return $resultRedirect->setPath($page);
    }

    private function getStreet($apiAddress)
    {
        $street = $apiAddress->line1;

        if(isset($apiAddress->line2))
            $street .= $apiAddress->line2;
        if(isset($apiAddress->line3))
            $street .= $apiAddress->line3;
        if(isset($apiAddress->line4))
            $street .= $apiAddress->line4;

        return $street;
    }

    private function createAddress($customer, $apiAddress, $defaultDelivery)
    {
        $this->logger->addInfo('Creating new address...');

        $street = $this->getStreet($apiAddress);

        $firstName = $customer->getFirstname();

        if(isset($apiAddress->firstName))
            $firstName = $apiAddress->firstName;

        $lastName = $customer->getLastname();

        if(isset($apiAddress->lastName))
            $lastName = $apiAddress->lastName;

        $company = null;

        if(isset($apiAddress->company))
            $company = $apiAddress->company;

        $address = $this->addressFactory->create();

        $delivery = '0';

        if($defaultDelivery == true)
            $delivery = '1';

        $address->setCustomerId($customer->getId())
            ->setFirstname($firstName)
            ->setLastname($lastName)
            ->setCompany($company)
            ->setCountryId($apiAddress->country)
            ->setPostcode($apiAddress->postalCode)
            ->setCity($apiAddress->city)
            ->setTelephone($apiAddress->phone)
            ->setStreet($street)
            ->setIsDefaultBilling('0')
            ->setIsDefaultShipping($delivery)
            ->setSaveInAddressBook('1')
            ->setCustomAttribute(AttributeSetup::DIFENGO_ID, $apiAddress->name);
        
        $address->save();

        $this->logger->addInfo('New address created: ' . $apiAddress->name);
    }

    private function updateAddress($address, $apiAddress)
    {
        $this->logger->addInfo('Updating existing address...');

        $save = false;

        if(isset($apiAddress->firstName) && $address->getFirstname() != $apiAddress->firstName) {
            $address->setFirstname($apiAddress->firstName);
            $save = true;
        }
        
        if(isset($apiAddress->lastName) && $address->getLastname() != $apiAddress->lastName) {
            $address->setFirstname($apiAddress->lastName);
            $save = true;
        }

        if(isset($apiAddress->company) && $address->getCompany() != $apiAddress->company) {
            $address->setCompany($apiAddress->company);
            $save = true;
        }

        $street = $this->getStreet($apiAddress);

        if($address->getStreet() != $street) {
            $address->setStreet($street);
            $save = true;
        }

        if($address->getPostcode() != $apiAddress->postalCode) {
            $address->setPostcode($apiAddress->postalCode);
            $save = true;
        }

        if($address->getCity() != $apiAddress->city) {
            $address->setCity($apiAddress->city);
            $save = true;
        }

        if($address->getTelephone() != $apiAddress->phone) {
            $address->setTelephone($apiAddress->phone);
            $save = true;
        }

        if($address->getCountryId() != $apiAddress->country) {
            $address->setCountryId($apiAddress->country);
            $save = true;
        }
            
        if($save == true)
        {
            $address->save();
            $this->logger->addInfo('Existing address updated.');
        }
    }

    private function deleteAddress($address)
    {
        $this->logger->addInfo('Deleting existing address...');

        $address->delete();

        $this->logger->addInfo('Existing address deleted.');
    }
}
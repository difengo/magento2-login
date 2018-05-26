<?php


namespace Difengo\Login\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\EmailNotificationInterface;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\State\InputMismatchException;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;

use Magento\Framework\Registry;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\FilterBuilder;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Customer
 *
 * @package Difengo\Login\Model
 */
class DifengoCustomer extends AbstractModel
{
    /**
     * @type \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @type \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerDataFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteria;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    protected $filterGroup;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CustomerFactory $customerFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param SearchCriteriaInterface $criteria
     * @param FilterGroup $filterGroup
     * @param FilterBuilder $filterBuilder
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CustomerFactory $customerFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaInterface $criteria,
        FilterGroup $filterGroup,
        FilterBuilder $filterBuilder,
        StoreManagerInterface $storeManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->customerFactory     = $customerFactory;
        $this->customerRepository  = $customerRepository;
        $this->searchCriteria      = $criteria;
        $this->filterGroup         = $filterGroup;
        $this->filterBuilder       = $filterBuilder;
        $this->customerDataFactory = $customerDataFactory;
        $this->storeManager        = $storeManager;
    }

    /**
     * @param $id
     * @param null $websiteId
     * @return \Magento\Customer\Model\Customer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLocalCustomerByDifengoId($id, $websiteId = null)
    {
        $this->filterGroup->setFilters(
            [
                $this->filterBuilder
                    ->setField('difengo_id')
                    ->setConditionType('eq')
                    ->setValue($id)
                    ->create()
            ]
        );
    
        $this->searchCriteria->setFilterGroups([$this->filterGroup]);
        $customersList = $this->customerRepository->getList($this->searchCriteria);
        $customers = $customersList->getItems();
        
        if(count($customers) == 1)
            return $customers[0];
        else
            return null;
    }

    /**
     * @param $data
     * @param $store
     * @return mixed
     * @throws \Exception
     */
    public function createDifengoCustomer($data, $store)
    {
        /** @var CustomerInterface $customer */
        $customer = $this->customerDataFactory->create();

        $customer->setFirstname($data['firstName'])
            ->setLastname($data['lastName'])
            ->setEmail($data['email'])
            ->setGender($data['gender'])
            ->setStoreId($store->getId())
            ->setWebsiteId($store->getWebsiteId())
            ->setCreatedIn($store->getName())
            ->setCustomAttribute('difengo_id', $data['id']);

        try {

            // If customer exists existing hash will be used by Repository
            $customer = $this->customerRepository->save($customer);

            $objectManager     = \Magento\Framework\App\ObjectManager::getInstance();
            $mathRandom        = $objectManager->get('Magento\Framework\Math\Random');
            $newPasswordToken  = $mathRandom->getUniqueHash();
            $accountManagement = $objectManager->get('Magento\Customer\Api\AccountManagementInterface');
            $accountManagement->changeResetPasswordLinkToken($customer, $newPasswordToken);

        } catch (AlreadyExistsException $e) {

            throw new InputMismatchException(
                __('A customer with the same Difengo Id already exists in an associated website.')
            );
        } catch (\Exception $e) {

            if ($customer->getId()) {
                $this->_registry->register('isSecureArea', true, true);
                $this->customerRepository->deleteById($customer->getId());
            }

            throw $e;
        }

        /** @var Customer $customer */
        $customer = $this->customerFactory->create()->load($customer->getId());

        return $customer;
    }
}
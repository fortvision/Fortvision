<?php

namespace Fortvision\Platform\Cron;
use Magento\Customer\Api\Data\CustomerInterface as Customer;
use Psr\Log\LoggerInterface;

// use Fortvision\Sync\Logger\Integration as LoggerIntegration;
use Fortvision\Platform\Provider\GeneralSettings;
use Fortvision\Platform\Service\ExportHistorical as SyncService;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Fortvision\Platform\Logger\Integration as LoggerIntegration;
use Fortvision\Platform\Model\Rest\HttpClient;
use Fortvision\Platform\Model\Api\MainService as MainVisionService;

/**
 * Class Sync
 * @package Fortvision\Sync\Cron
 */
class Sync
{
    /**
     * @var SyncService
     */
    protected $sync;
    protected $generalSettings;
    protected $categoryRepository;
    protected $productRepository;
    protected $storeManager;
    private LoggerIntegration $logger;
    private LoggerInterface $_logger;
    private HttpClient $httpClient;
    private \Magento\Customer\Model\CustomerFactory $customerFactory;
    private \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory;
    private MainVisionService $mainVisionService;


    /**
     * Sync constructor.
     * @param SyncService $sync
     */
    public function __construct(
        SyncService $sync,
        HttpClient $httpClient,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        MainVisionService                                  $mainVisionService,

        GeneralSettings $generalSettings,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        LoggerInterface $_logger,
        LoggerIntegration $logger
    ) {
        $this->httpClient = $httpClient;
        $this->customerFactory  = $customerFactory;
        $this->subscriberFactory  = $subscriberFactory;
        $this->mainVisionService = $mainVisionService;

        $this->generalSettings = $generalSettings;
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;


          $this->logger = $logger;
          $this->_logger = $_logger;
    }
    private const PATTERN_NAME = '/(?:[\p{L}\p{M}\,\-\_\.\'\s\d]){1,255}+/u';


    private function isValidName($nameValue): bool
    {

        $this->logger->debug('isValidName1:');

        if ($nameValue != null) {
            $this->logger->debug('isValidName2:');

            if (preg_match(self::PATTERN_NAME, $nameValue, $matches)) {
                $this->logger->debug('isValidName3:'.$nameValue.' '.($matches[0] == $nameValue?'yes':'no'));

                return $matches[0] == $nameValue;
            }
        }

        return true;
    }
    public function addCustomer($data, $publishers) {
        $websiteid = $this->getWebSiteIdByPublisherId($data['publisherId'],$publishers);
                $this->logger->debug('addcustomer0:'.$websiteid." ".json_encode($data));

        $email = $data['email'];
        if (!$email || $email==='no-email') return false;

         $this->logger->debug('addcustomer01:'.$websiteid." ".json_encode($data));

        $phone = $data['phone'];
        $lastName = $data['lastName'] ?? 'FORTVISION';
        $firstname = $data['firstName'] ?? 'FORTVISION';
        if (!$this->isValidName($lastName)) $lastName='FORTVISION';
        if (!$this->isValidName($firstname)) $firstname='FORTVISION';

        $isSubscribed = $data['isSubscribed'];
        $this->logger->debug('addcustomer1:'.$websiteid.": ".json_encode($data));

        $this->logger->debug('addcustome0');
        $customer   = $this->customerFactory->create();
        //$this->logger->debug('addcustome1');

        $customer->setWebsiteId($websiteid);
        $check = $customer->loadByEmail($email);
        if (!$check->getId()) {
            $this->logger->debug('addcustome2:' . " " . $email);

            // Preparing data for new customer
            $customer->setEmail($email);
            // $this->logger->debug('addcustome3');
            $this->logger->debug('addcustome3:' . $firstname .' '. $lastName);

            $customer->setFirstname($firstname);
            // $customer->($firstname);
            $this->logger->debug('addcustome3-00:');

            $customer->setLastname($lastName);
          //  $customer->setLastname("FORTVISION");

        //    $customer->setPassword("password");
            $customer->setConfirmation(null);
            $customer->setForceConfirmed(true);
            $this->logger->debug('addcustome3-44-1:' . $email);

            $error = false;
            try {


                $customer->save();
                $this->logger->debug('addcustome3-55:');

            }
            catch (\Exception $e) {
                $this->logger->debug('EXC:'.$firstname.':'.$email.':'.$e->getMessage());
                $error = true;
            }

            // $customer->sendNewAccountEmail();
            //  $this->logger->debug('addcustome3-66:');

            //    $this->assertEquals($customerData[Customer::ID], $searchResults['items'][0][Customer::ID]);
            //   $this->assertEquals($subscribeStatus, $searchResults['items'][0]['extension_attributes']['is_subscribed']);

            $this->logger->debug('addcustomerAQQ:' . ($error?'error':'success').$websiteid . " " . json_encode($data));
        } else $this->logger->debug('skipped:'.$email);

    }


    public function subsunsubs($data, $publishers)
    {
        $websiteid = $this->getWebSiteIdByPublisherId($data['publisherId'], $publishers);
        $email = $data['email'];
        $phone = $data['phone'];
        $this->logger->debug('subsunsubs0:' . $email . $websiteid . " " . json_encode($data));
        $customerBase = $this->customerFactory->create()->setWebsiteId($websiteid);
        if ($customerBase) {
            $this->logger->debug('subsunsubsCUSTM0');

            $customer = $customerBase->loadByEmail($email);
            if (!$customer) {
                $this->logger->debug('subsunsubsCUSTM22:'.get_class($customer));

                try {
                    $customer_id = $customer->getId();

                    $this->logger->debug("idd" . $customer_id);
                }
                catch (\Exception $e) {
                    $this->logger->debug("isSubscribed".$e->getMessage());

                }
                $userIsSubscriber = $this->subscriberFactory->loadByCustomerId($customer_id);

                if ($userIsSubscriber->isSubscribed()) {
                    $this->logger->debug("isSubscribed");

                    // return Customer is subscribed
                } else {
                    $this->logger->debug("isnotSubscribed");

                    // return Customer is not subscribed
                }
            } else         $this->logger->debug('SKIPPED' . $email);
            //    $this->logger->debug('subsA:'.$websiteid." ".json_encode($data));

        }
    }


    public function getWebSiteIdByPublisherId($publisherId, $publishers) {
        $websites=$publishers['websites'];
        $websiteid= 0;
        foreach ($websites as $website) {
            if ($website['publisherId']==$publisherId) {
                $websiteid=$website['siteId'];
            }

        }

        // $this->logger->debug('QgetWebSiteIdByPublisherId'.$websiteid.' '.$publisherId.' '.json_encode($websites));
        return $websiteid;
    }

    public function execute()
    {
        try {
            $magentoid  =  $this->generalSettings->getMagentoId();
         ///   $magentoid = '3e044f36e84b6c6b897ace6b36d351feb9518744676985f3ad797400608b088d'; //debug!
            $this->logger->debug('dododo'.$magentoid);

            $answer=$this->httpClient->doCloudflareRequest(['kind'=>'getfromqueue', 'mid'=>$magentoid]);
            $completed=[];
            if (isset($answer) && isset($answer['result']) && isset($answer['result']['queue'])) {
                $currentQueue = $answer['result']['queue'];

                foreach ($currentQueue as $task) {

                    switch ($task['kind']) {
                        case 'resync':
                            $this->mainVisionService->doSyncDataRegular();

                            break;
                        case 'sendlogs':
                            // TODO SEND LOGS

                            break;

                        case 'subs':
                            //$this->logger->debug('subs'.json_encode($task));

                            $this->subsunsubs($task,$answer['result']['publishers']);
                            if (isset($task['id']))  $completed[]=$task['id'];


                            $this->logger->debug('subs2!!!');

                            break;

                        case 'addcustomer':
                            $result = $this->addCustomer($task,$answer['result']['publishers']);

                            $completed[]=$task['id'];

                            break;
                    }
                }

                $this->logger->debug('completed:'.json_encode($completed));
                $answer2=$this->httpClient->doCloudflareRequest(['kind'=>'parsed', 'ids'=>$completed,'mid'=>$magentoid]);
                $this->logger->debug('completed2:');


            }




            $this->_logger->debug('dododo2');
            return "qweqwe";
            $this->sync->process();
        } catch (\Exception $e) {
         //   $this->logger->critical($e->getMessage());
        }
    }
}

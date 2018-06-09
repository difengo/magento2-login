<?php

namespace Difengo\Login\Helper;

use Magento\Framework\App\Helper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Ramsey\Uuid\Uuid;

class ApiClient extends AbstractHelper 
{
    const AUTHORIZE_GET_ENDPOINT  =	"/api/authorize";
    const RECEIPT_POST_ENDPOINT  =	"/api/receipt/save";
    const CUSTOMER_POST_ENDPOINT =	"/api/customer/save";
    const CUSTOMER_GET_ENDPOINT  =	"/api/customer/get";

    /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    protected $curl;

    /**
    * @var \Magento\Framework\Serialize\SerializerInterface
    */
    protected $serializer;

    /**
    * @var \Psr\Log\LoggerInterface
    */
    protected $logger;

    /**
    * @var \Difengo\Login\Helper\Data
    */
    protected $configuration;

    protected $tokenId;
    protected $tokenExpiresAt;

    public function __construct(
        Curl $curl, 
        SerializerInterface $serializer, 
        Data $configuration, 
        LoggerInterface $logger
    ) 
    {
        $this->curl = $curl;
        $this->serializer = $serializer;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    public function getRemoteCustomerData($token)
    {
        $this->logger->addInfo('Retrieving customer data from remote difengo api...');

        //TODO: Validate passed token

        if($this->authenticate() == true)
        {
            $request = $this->getCustomerRequest($token);

            $uri = $this->configuration->getApiUri() . self::CUSTOMER_GET_ENDPOINT;

            //TODO: remove in production
            $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);

            $this->curl->setOption(CURLOPT_USERAGENT, 'magento2-plugin/1.0');
            $this->curl->setOption(CURLOPT_PORT, $this->configuration->getApiPort());
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('Authorization', 'Bearer ' . $this->tokenId);

            $this->curl->post($uri, $request);
            $json_response = $this->curl->getBody();

            if($json_response != null)
            {
                $this->logger->addInfo('JSON Response: ' . $json_response);

                $response = json_decode($json_response);

                if($response->code == 200 || $response->code == 201)
                {
                    $customer = $response->message;

                    return $customer;
                }
            }
        }

        return null;
    }

    private function getCustomerRequest($token) {

        $this->logger->addInfo('Creating customer request...');

        $uuid4 = Uuid::uuid4();

        $request = [
          "id" => $uuid4->toString(),
          "objType" => "Customer",
          "objData" => $token,
          "certId" => $this->configuration->getApiSecret()
        ];

        $json_request = $this->serializer->serialize($request);

        $this->logger->addInfo('JSON request: ' . $json_request);

        return gzencode($json_request, 9);
    }

    private function authenticate()
    {
        if($this->tokenId == null || $this->tokenExpiresAt < time())
        {
            $this->logger->addInfo('Authenticating with remote difengo api...');

            $uri = $this->configuration->getApiUri() . self::AUTHORIZE_GET_ENDPOINT;

            $this->logger->addInfo('URI: ' . $uri);

            $login = $this->configuration->getApiSecret();

            $this->logger->addInfo('Login: ' . $login);

            $pwd = $this->configuration->getApiKey();

            $this->logger->addInfo('Pwd: ' . $pwd);

            // TODO: remove this in production
            $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);

            $this->curl->setOption(CURLOPT_USERAGENT, 'magento2-plugin/1.0');
            $this->curl->setOption(CURLOPT_PORT, $this->configuration->getApiPort());
            $this->curl->setOption(CURLOPT_USERPWD, $login . ":" . $pwd);
            $this->curl->addHeader('Content-Type', 'application/json');

            $this->curl->get($uri);
            $json_response = $this->curl->getBody();

            if($json_response != null)
            {
                $this->logger->addInfo('JSON Response: ' . $json_response);

                $response = json_decode($json_response);

                if($response->code == 200 || $response->code == 201)
                {
                    $this->tokenId = $response->message->id;
                    $this->tokenExpiresAt = strtotime($response->message->expiresAt);

                    $this->logger->addInfo('Authenticated with remote difengo api.');

                    return true;
                } 
                else 
                {
                    $this->logger->addError('Error while authenticating with remote difengo api: ' . $response->message);
                    
                    return false;
                }
            }
            else
            {
                $this->logger->addError('Cannot authenticate with remote difengo api.');

                return false;
            }
        }

        return true;
    }
}
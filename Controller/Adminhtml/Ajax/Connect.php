<?php

namespace Spod\Sync\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Spod\Sync\Helper\CacheHelper;
use Spod\Sync\Helper\ConfigHelper;
use Spod\Sync\Helper\StatusHelper;
use Spod\Sync\Model\ApiReader\AuthenticationHandler;
use Spod\Sync\Model\CrudManager\WebhookManager;
use Spod\Sync\Model\Mapping\WebhookEvent;

class Connect extends Action
{
    /**
     * @var AuthenticationHandler
     */
    private $authHandler;
    /**
     * @var CacheHelper
     */
    private $cacheHelper;
    /**
     * @var JsonFactory
     */
    private $jsonResultFactory;
    /**
     * @var ConfigHelper
     */
    private $configHelper;
    /**
     * @var StatusHelper
     */
    private $statusHelper;
    /**
     * @var WebhookManager
     */
    private $webhookManager;

    public function __construct(
        AuthenticationHandler $authHandler,
        CacheHelper $cacheHelper,
        ConfigHelper $configHelper,
        Context $context,
        JsonFactory $jsonResultFactory,
        StatusHelper $statusHelper,
        WebhookManager $webhookManager
    ) {
        parent::__construct($context);
        $this->authHandler = $authHandler;
        $this->cacheHelper = $cacheHelper;
        $this->configHelper = $configHelper;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->statusHelper = $statusHelper;
        $this->webhookManager = $webhookManager;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Spod_Sync::spodsync');
    }

    public function execute()
    {
        $apiToken = $this->getRequest()->getParam('apiToken');
        $data = [];

        if ($this->authHandler->isTokenValid($apiToken)) {
            $this->handleValidKey($apiToken);

            $data['error'] = 0;
            $data['message'] = 'API key is valid';
            $data['installDate'] = $this->statusHelper->getInstallDate();
            $data['initsyncStartDate'] = $this->statusHelper->getInitialSyncStartDate();
            $data['initsyncEndDate'] = $this->statusHelper->getInitialSyncEndDate();

        } else {
            $data['error'] = 1;
            $data['message'] = 'Invalid API key';
        }

        $result = $this->jsonResultFactory->create();
        $result->setData($data);

        return $result;
    }

    private function handleValidKey(string $apiToken)
    {
        $this->configHelper->saveApiToken($apiToken);
        $this->cacheHelper->clearConfigCache();
        $this->statusHelper->setInstallDate();
        $this->webhookManager->saveWebhookEvent(WebhookEvent::EVENT_ARTICLE_INITALSYNC, "");
    }
}

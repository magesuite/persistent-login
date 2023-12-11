<?php

namespace MageSuite\PersistentLogin\Observer;

class LogInCustomer implements \Magento\Framework\Event\ObserverInterface
{
    const CUSTOMER_WAS_LOGGED_DURING_CURRENT_REQUEST_KEY = 'CUSTOMER_LOGGED_IN_FROM_PERSISTENT';

    protected \Magento\Persistent\Helper\Session $persistentSession;
    protected \Magento\Persistent\Helper\Data $persistentData;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Magento\Framework\App\Response\RedirectInterface $redirect;
    protected \Psr\Log\LoggerInterface $logger;
    protected \Magento\Framework\Registry $registry;
    protected \Magento\Framework\App\RequestInterface $request;
    protected \Magento\Framework\App\ActionFlag $actionFlag;

    public function __construct(
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\Persistent\Helper\Data $persistentData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\ActionFlag $actionFlag
    ) {
        $this->persistentSession = $persistentSession;
        $this->persistentData = $persistentData;
        $this->customerSession = $customerSession;
        $this->redirect = $redirect;
        $this->logger = $logger;
        $this->registry = $registry;
        $this->request = $request;
        $this->actionFlag = $actionFlag;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->persistentData->isEnabled()) {
            return;
        }

        if ($this->persistentSession->isPersistent() && $this->customerSession->isLoggedIn()) {
            return;
        }

        $customerId = $this->persistentSession->getSession()->getCustomerId();

        if (!is_numeric($customerId)) {
            return;
        }

        if ($customerId <= 0) {
            return;
        }

        try {
            $this->customerSession->loginById($customerId);

            $this->registry->register(self::CUSTOMER_WAS_LOGGED_DURING_CURRENT_REQUEST_KEY, true);

            if (!$this->request->isAjax()) {
                $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);

                $controller = $observer->getControllerAction();
                $request = $controller->getRequest();
                $this->redirect->redirect($controller->getResponse(), $request->getUriString());
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Logging in persistent customer failed, reason: %s', $e->getMessage()));
        }
    }
}

<?php

namespace MageSuite\PersistentLogin\Observer;

class LogOutCustomerWhenTokenDoesNotMatch implements \Magento\Framework\Event\ObserverInterface
{
    const CUSTOMER_WAS_LOGGED_DURING_CURRENT_REQUEST_KEY = 'CUSTOMER_LOGGED_IN_FROM_PERSISTENT';

    protected \Magento\Persistent\Helper\Session $persistentSession;
    protected \Magento\Persistent\Helper\Data $persistentData;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Magento\Framework\App\Response\RedirectInterface $redirect;
    protected \Psr\Log\LoggerInterface $logger;
    protected \Magento\Framework\App\RequestInterface $request;
    protected \Magento\Framework\App\ActionFlag $actionFlag;
    protected \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager;

    public function __construct(
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\Persistent\Helper\Data $persistentData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
    ) {
        $this->persistentSession = $persistentSession;
        $this->persistentData = $persistentData;
        $this->customerSession = $customerSession;
        $this->redirect = $redirect;
        $this->logger = $logger;
        $this->request = $request;
        $this->actionFlag = $actionFlag;
        $this->cookieManager = $cookieManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->persistentData->isEnabled()) {
            return;
        }

        if (!$this->customerSession->isLoggedIn()) {
            return;
        }

        $key = $this->cookieManager->getCookie(\Magento\Persistent\Model\Session::COOKIE_NAME);

        if (empty($key)) {
            return;
        }

        $persistentSession = $this->persistentSession->getSession();

        if ($persistentSession->getId()) {
            return;
        }

        try {
            $persistentSession->removePersistentCookie();
            $this->customerSession->logout();

            $controller = $observer->getControllerAction();
            $request = $controller->getRequest();

            if (!$this->request->isAjax()) {
                $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);

                $this->redirect->redirect($controller->getResponse(), $request->getUriString());
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Logging out customer when his key is expired failed, reason: %s', $e->getMessage()));
        }
    }
}

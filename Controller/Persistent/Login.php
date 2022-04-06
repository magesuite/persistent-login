<?php

namespace MageSuite\PersistentLogin\Controller\Persistent;

/*
 * Performs login when customer enters fully cached page and Javascript detects that it is not logged in currently
 */
class Login implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    protected \Magento\Framework\Controller\ResultFactory $resultFactory;
    protected \Magento\Framework\Registry $registry;

    public function __construct(
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultFactory = $resultFactory;
        $this->registry = $registry;
    }

    public function execute()
    {
        $wasCustomerLoggedDuringCurrentRequest = (bool)$this->registry->registry(
            \MageSuite\PersistentLogin\Observer\LogInCustomer::CUSTOMER_WAS_LOGGED_DURING_CURRENT_REQUEST_KEY
        );

        $jsonResult = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $jsonResult->setData(['refresh_page' => $wasCustomerLoggedDuringCurrentRequest]);

        return $jsonResult;
    }
}

<?php

namespace MageSuite\PersistentLogin\Plugin\Customer\Api\AccountManagementInterface;

class ResetTokenAfterPasswordChange
{
    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;
    protected \Magento\Persistent\Model\Session $session;
    protected \Magento\Framework\Math\Random $mathRandom;
    protected \Magento\Persistent\Helper\Data $persistentData;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Persistent\Model\Session $session,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Persistent\Helper\Data $persistentData,
        \Magento\Customer\Model\Session $customerSession,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerRepository = $customerRepository;
        $this->session = $session;
        $this->mathRandom = $mathRandom;
        $this->persistentData = $persistentData;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    public function afterChangePassword(\Magento\Customer\Api\AccountManagementInterface $subject, $result, $email)
    {
        if (!$this->persistentData->isEnabled()) {
            return $result;
        }

        try {
            $customer = $this->customerRepository->get($email);

            $session = $this->session->loadByCustomerId($customer->getId());

            if (!$session->getId()) {
                return $result;
            }

            $session->setKey($this->mathRandom->getRandomString(\Magento\Persistent\Model\Session::KEY_LENGTH));
            $session->save();

            $session->setPersistentCookie(
                $this->persistentData->getLifeTime(),
                $this->customerSession->getCookiePath()
            );
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Updating persistent login token after password change failed, reason: %s',
                $e->getMessage()
            ));
        }

        return $result;
    }
}
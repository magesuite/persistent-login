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
        $this->updatePersistentToken($email, true);

        return $result;
    }

    public function afterResetPassword(\Magento\Customer\Api\AccountManagementInterface $subject, $result, $email)
    {
        $this->updatePersistentToken($email, false);

        return $result;
    }

    protected function updatePersistentToken($email, $setCookie = false)
    {
        if (!$this->persistentData->isEnabled()) {
            return;
        }

        try {
            $customer = $this->customerRepository->get($email);

            $session = $this->session->loadByCustomerId($customer->getId());

            if (!$session->getId()) {
                return;
            }

            $session->setForceKeyRegeneration(true);
            $session->setKey();
            $session->save();

            if ($setCookie) {
                $session->setPersistentCookie(
                    $this->persistentData->getLifeTime(),
                    $this->customerSession->getCookiePath()
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Updating persistent login token after password change failed, reason: %s',
                $e->getMessage()
            ));
        }
    }
}

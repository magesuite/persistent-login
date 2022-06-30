<?php

namespace MageSuite\PersistentLogin\Plugin\Persistent\Observer\SynchronizePersistentOnLoginObserver;

class MigrateOldKeyOnLogin
{
    protected \Magento\Persistent\Helper\Data $persistentData;
    protected \Magento\Persistent\Helper\Session $persistentSession;
    protected \Magento\Customer\Model\Session $customerSession;

    public function __construct(
        \Magento\Persistent\Helper\Data $persistentData,
        \Magento\Persistent\Helper\Session $persistentSession,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->persistentData = $persistentData;
        $this->persistentSession = $persistentSession;
        $this->customerSession = $customerSession;
    }

    public function afterExecute(\Magento\Persistent\Observer\SynchronizePersistentOnLoginObserver $subject, $result)
    {
        $session = $this->persistentSession->getSession();

        if (strlen($session->getKey()) !== \MageSuite\PersistentLogin\Plugin\Persistent\Model\Session\LoadSessionUsingHashedKeyFromCookie::OLD_UNHASHED_KEY_LENGTH) {
            return $result;
        }

        $session->setForceKeyRegeneration(true);
        $session->setKey();
        $session->save();

        $session->setPersistentCookie(
            $this->persistentData->getLifeTime(),
            $this->customerSession->getCookiePath()
        );

        return $result;
    }
}

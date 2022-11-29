<?php

namespace MageSuite\PersistentLogin\Plugin\Persistent\Model\Session;

class LoadSessionUsingHashedKeyFromCookie
{
    public const OLD_UNHASHED_KEY_LENGTH = 50;

    protected \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager;
    protected \Magento\Persistent\Helper\Data $persistentData;
    protected \Magento\Customer\Model\Session $customerSession;
    protected \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData $generateHashBasedOnCustomerData;

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Persistent\Helper\Data $persistentData,
        \Magento\Customer\Model\Session $customerSession,
        \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData $generateHashBasedOnCustomerData
    ) {
        $this->cookieManager = $cookieManager;
        $this->persistentData = $persistentData;
        $this->customerSession = $customerSession;
        $this->generateHashBasedOnCustomerData = $generateHashBasedOnCustomerData;
    }

    /**
     * We need to hash key from the cookie to load correct entry from the database
     */
    public function aroundLoadByCookieKey(\Magento\Persistent\Model\Session $subject, callable $proceed, $key = null)
    {
        if (null === $key) {
            $key = $this->cookieManager->getCookie(\Magento\Persistent\Model\Session::COOKIE_NAME);

            if ($this->isOldKey($key)) {
                $key = $this->migrateOldKeyToHashedKey($subject, $key);
            }
        }

        if($key === null) {
            return $subject;
        }

        $key = hash('sha256', $key);

        if ($key) {
            $subject->load($key, 'key');
        }

        return $subject;
    }

    protected function migrateOldKeyToHashedKey(\Magento\Persistent\Model\Session $subject, ?string $key)
    {
        $subject->load($key, 'key');

        if (!$subject->getId()) {
            return $key;
        }

        $subject->setForceKeyRegeneration(true);
        $subject->setKey();
        $subject->save();

        $customerId = $subject->getCustomerId();

        $key = $this->generateHashBasedOnCustomerData->execute($customerId);

        $subject->setPersistentCookie(
            $this->persistentData->getLifeTime(),
            $this->customerSession->getCookiePath()
        );

        // $subject->setPersistentCookie does not modify global $_COOKIE array causing next plugin
        // run to still use the old value, therefore we need to set it manually
        $_COOKIE[\Magento\Persistent\Model\Session::COOKIE_NAME] = $key; // phpcs:ignore

        return $key;
    }

    protected function isOldKey($key): bool
    {
        if ($key === null) {
            return false;
        }

        return strlen($key) === self::OLD_UNHASHED_KEY_LENGTH;
    }
}

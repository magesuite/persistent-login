<?php

namespace MageSuite\PersistentLogin\Plugin\Persistent\Model\Session;

class GenerateHashedKeyForDatabase
{
    protected \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData $generateHashBasedOnCustomerData;

    public function __construct(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData $generateHashBasedOnCustomerData)
    {
        $this->generateHashBasedOnCustomerData = $generateHashBasedOnCustomerData;
    }

    /**
     * We need to set key in the database as hash of key that will be stored in the COOKIE
     */
    public function aroundSetData(\Magento\Persistent\Model\Session $subject, callable $proceed, $key, $value = null)
    {
        if ($key !== 'key' ||
            (!$subject->isObjectNew() && !$subject->getForceKeyRegeneration())
        ) {
            return $proceed($key, $value);
        }

        $customerId = $subject->getCustomerId();

        $value = $this->generateHashBasedOnCustomerData->execute($customerId);
        $value = hash('sha256', $value);

        return $proceed($key, $value);
    }
}

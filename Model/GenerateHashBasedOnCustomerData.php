<?php

namespace MageSuite\PersistentLogin\Model;

class GenerateHashBasedOnCustomerData
{
    protected \Magento\Framework\App\DeploymentConfig $deploymentConfig;
    protected \Magento\Customer\Model\CustomerRegistry $customerRegistry;

    public function __construct(
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->customerRegistry = $customerRegistry;
    }

    public function execute(int $customerId)
    {
        $passwordHash = $this->customerRegistry->retrieveSecureData($customerId)
            ->getPasswordHash();

        $cryptKey = $this->deploymentConfig->get(\Magento\Framework\Encryption\Encryptor::PARAM_CRYPT_KEY);

        return hash('sha256', $cryptKey . $customerId . $passwordHash);
    }
}

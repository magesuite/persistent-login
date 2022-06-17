<?php

namespace MageSuite\PersistentLogin\Plugin\Persistent\Model\Session;

class GenerateKeyForCookie
{
    protected \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory;
    protected \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager;
    protected \Magento\Framework\App\Request\Http $request;
    protected \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData $generateHashBasedOnCustomerData;

    public function __construct(
        \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData $generateHashBasedOnCustomerData,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieManager = $cookieManager;
        $this->request = $request;
        $this->generateHashBasedOnCustomerData = $generateHashBasedOnCustomerData;
    }

    /**
     * Cookie needs to store original unhashed that will be hashed and compared with database values
     */
    public function aroundSetPersistentCookie(\Magento\Persistent\Model\Session $subject, callable $proceed, $duration, $path)
    {
        $customerId = $subject->getCustomerId();

        $value = $this->generateHashBasedOnCustomerData->execute($customerId);

        $this->setCookie($value, $duration, $path);

        return $subject;
    }

    protected function setCookie($value, $duration, $path)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($path)
            ->setSecure($this->request->isSecure())
            ->setHttpOnly(true)
            ->setSameSite('Lax');

        $this->cookieManager->setPublicCookie(
            \Magento\Persistent\Model\Session::COOKIE_NAME,
            $value,
            $publicCookieMetadata
        );
    }
}

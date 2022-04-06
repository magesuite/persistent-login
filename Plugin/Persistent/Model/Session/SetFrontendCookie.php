<?php

namespace MageSuite\PersistentLogin\Plugin\Persistent\Model\Session;

class SetFrontendCookie
{
    public const COOKIE_NAME = 'persistent_login_used';

    protected \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager;
    protected \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory;
    protected \Magento\Framework\App\Request\Http $request;
    protected \Magento\Framework\Session\Config\ConfigInterface $sessionConfig;

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Session\Config\ConfigInterface $sessionConfig
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->request = $request;
        $this->sessionConfig = $sessionConfig;
    }

    public function afterRemovePersistentCookie(\Magento\Persistent\Model\Session $subject, $result)
    {
        $cookieMetadata = $this->cookieMetadataFactory->createSensitiveCookieMetadata();
        $cookieMetadata->setPath($this->sessionConfig->getCookiePath());

        $this->cookieManager->deleteCookie(self::COOKIE_NAME, $cookieMetadata);

        return $result;
    }

    /**
     * @param int $duration Time in seconds.
     * @param string $path
     * @return $this
     * @api
     */
    public function afterSetPersistentCookie(\Magento\Persistent\Model\Session $subject, $result, $duration, $path)
    {
        $this->setCookie(true, $duration, $path);

        return $result;
    }

    /**
     * @param int $duration Time in seconds.
     * @param string $path
     * @return $this
     */
    public function afterRenewPersistentCookie(\Magento\Persistent\Model\Session $subject, $result, $duration, $path)
    {
        if ($duration === null) {
            return $result;
        }

        $value = $this->cookieManager->getCookie(self::COOKIE_NAME);

        if (null !== $value) {
            $this->setCookie($value, $duration, $path);
        }

        return $result;
    }

    protected function setCookie($value, $duration, $path)
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration($duration)
            ->setPath($path)
            ->setSecure($this->request->isSecure())
            ->setHttpOnly(false)
            ->setSameSite('Lax');

        $this->cookieManager->setPublicCookie(
            self::COOKIE_NAME,
            $value,
            $publicCookieMetadata
        );
    }
}

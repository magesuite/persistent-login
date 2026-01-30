<?php

declare(strict_types=1);

namespace MageSuite\PersistentLogin\Test\Integration\Observer;

class LogInCustomerTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected const FIXTURE_CUSTOMER_EMAIL = 'customer@example.com';

    protected ?\Magento\TestFramework\ObjectManager $objectManager = null;
    protected ?\Magento\Customer\Model\Session $customerSession = null;
    protected ?\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository = null;
    protected ?\Magento\Persistent\Model\SessionFactory $persistentSessionFactory = null;
    protected ?\Magento\Framework\Stdlib\CookieManagerInterface $cookieManager = null;
    protected ?\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->persistentSessionFactory = $this->objectManager->get(\Magento\Persistent\Model\SessionFactory::class);
        $this->customerRepository = $this->objectManager->get(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        $this->customerSession = $this->objectManager->create(\Magento\Customer\Model\Session::class);
        $this->cookieManager = $this->objectManager->create(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        $this->cookieMetadataFactory = $this->objectManager->create(\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Persistent/_files/persistent_with_customer_quote_and_cookie.php
     * @magentoConfigFixture current_store persistent/options/enabled 1
     * @magentoConfigFixture current_store persistent/options/logout_clear 0
     */
    public function testItLogsCustomerInWhenHisSessionIsPersistedInCookie(): void
    {
        $this->assertFalse($this->customerSession->isLoggedIn());

        $this->setPersistentLoginCookie();
        $this->dispatch('customer/account/login');

        $this->assertTrue($this->customerSession->isLoggedIn());
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Persistent/_files/persistent_with_customer_quote_and_cookie.php
     * @magentoConfigFixture current_store persistent/options/enabled 1
     * @magentoConfigFixture current_store persistent/options/logout_clear 0
     */
    public function testItRedirectsUserWhenObserverLogsUserIn(): void
    {
        $this->assertFalse($this->customerSession->isLoggedIn());

        $this->setPersistentLoginCookie();
        $this->dispatch('customer/account/login');

        $this->assertRedirect();
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Persistent/_files/persistent_with_customer_quote_and_cookie.php
     * @magentoConfigFixture current_store persistent/options/enabled 1
     */
    public function testItDoesNotLogCustomerWhenCookieIsNotThere(): void
    {
        $this->assertFalse($this->customerSession->isLoggedIn());

        $this->removePersistentCookie();
        $this->dispatch('customer/account/login');

        $this->assertFalse($this->customerSession->isLoggedIn());
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Persistent/_files/persistent_with_customer_quote_and_cookie.php
     * @magentoConfigFixture current_store persistent/options/enabled 0
     */
    public function testItDoesNotLogCustomerWhenFeatureIsDisabled(): void
    {
        $this->assertFalse($this->customerSession->isLoggedIn());

        $this->dispatch('customer/account/login');

        $this->assertFalse($this->customerSession->isLoggedIn());
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDataFixture MageSuite_PersistentLogin::Test/Integration/_files/persistent_with_customer_quote_and_cookie_before_migration.php
     * @magentoConfigFixture current_store persistent/options/enabled 1
     * @magentoConfigFixture current_store persistent/options/logout_clear 0
     */
    public function testItMigratesTokenToTheNewFormatWhenUserEntersWithTheOldOne(): void
    {
        $expectedKeyInCookie = 'de8d828f14f22750a0d007c1a3afc2cf2f8fb21b63b71c64633f2fb8d0a090a6';
        $expectedKeyInDatabase = 'f8075031ddd589109879650af93566b381bb53290aa11297cbcd4e824a44ffba';

        $generateHashKeyMock = $this->getMockBuilder(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class)
            ->disableOriginalConstructor()
            ->getMock();

        // we need a constant value that does not depend on crypt key (random on every test execution)
        $generateHashKeyMock->method('execute')->willReturn($expectedKeyInCookie);

        $this->objectManager->removeSharedInstance(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class);
        $this->objectManager->addSharedInstance(
            $generateHashKeyMock,
            \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class
        );

        $oldFormatCookieKey = 'f495be79bad3d692686f63d43283c1f8f495be79bad3d69268';
        $this->setCookie($oldFormatCookieKey);

        $this->dispatch('customer/account/login');

        $keyInDatabase = $this->getPersistentSessionModel()->getKey();
        $keyInCookie = $this->cookieManager->getCookie(\Magento\Persistent\Model\Session::COOKIE_NAME);

        $this->assertTrue($this->customerSession->isLoggedIn());
        $this->assertEquals($expectedKeyInCookie, $keyInCookie);
        $this->assertEquals($expectedKeyInDatabase, $keyInDatabase);
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDataFixture MageSuite_PersistentLogin::Test/Integration/_files/persistent_with_customer_quote_and_cookie_before_migration.php
     * @magentoConfigFixture current_store persistent/options/enabled 1
     */
    public function testItMigratesTokenToTheNewFormatWhenUserLogsIn(): void
    {
        $expectedKeyInCookie = 'de8d828f14f22750a0d007c1a3afc2cf2f8fb21b63b71c64633f2fb8d0a090a6';
        $expectedKeyInDatabase = 'f8075031ddd589109879650af93566b381bb53290aa11297cbcd4e824a44ffba';

        $generateHashKeyMock = $this->getMockBuilder(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class)
            ->disableOriginalConstructor()
            ->getMock();

        // we need a constant value that does not depend on crypt key (random on every test execution)
        $generateHashKeyMock->method('execute')->willReturn($expectedKeyInCookie);

        $this->objectManager->removeSharedInstance(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class);
        $this->objectManager->addSharedInstance(
            $generateHashKeyMock,
            \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class
        );

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue([
            'login' => [
                'username' => \MageSuite\PersistentLogin\Test\Integration\Observer\LogInCustomerTest::FIXTURE_CUSTOMER_EMAIL,
                'password' => 'password',
            ],
            'persistent_remember_me' => 'on',
        ]);

        $this->dispatch('customer/account/loginPost');

        $keyInDatabase = $this->getPersistentSessionModel()->getKey();

        $this->assertTrue($this->customerSession->isLoggedIn());
        $this->assertEquals($expectedKeyInDatabase, $keyInDatabase);
    }

    protected function setPersistentLoginCookie(): void
    {
        $this->getPersistentSessionModel()->setPersistentCookie(86400, '/');
    }

    protected function removePersistentCookie(): void
    {
        $this->getPersistentSessionModel()->removePersistentCookie();
    }

    /**
     * @return \Magento\Persistent\Model\Session
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPersistentSessionModel(): \Magento\Persistent\Model\Session
    {
        $customer = $this->customerRepository->get(self::FIXTURE_CUSTOMER_EMAIL);
        /** @var \Magento\Persistent\Model\Session $sessionModel */
        $sessionModel = $this->persistentSessionFactory->create();
        $sessionModel->loadByCustomerId($customer->getId());

        return $sessionModel;
    }

    protected function setCookie(string $value): void
    {
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(31536000)
            ->setPath('/')
            ->setSecure($this->getRequest()->isSecure())
            ->setHttpOnly(true)
            ->setSameSite('Lax');
        $this->cookieManager->setPublicCookie(
            \Magento\Persistent\Model\Session::COOKIE_NAME,
            $value,
            $publicCookieMetadata
        );
    }
}

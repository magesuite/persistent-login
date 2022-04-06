<?php

namespace MageSuite\PersistentLogin\Test\Integration\Observer;

class LogInCustomerTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected const FIXTURE_CUSTOMER_EMAIL = 'customer@example.com';

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var mixed
     */
    protected $persistentSessionFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->persistentSessionFactory = $this->objectManager->get(\Magento\Persistent\Model\SessionFactory::class);
        $this->customerRepository = $this->objectManager->get(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        $this->customerSession = $this->objectManager->create(\Magento\Customer\Model\Session::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Persistent/_files/persistent_with_customer_quote_and_cookie.php
     * @magentoConfigFixture current_store persistent/options/enabled 1
     */
    public function testItLogsCustomerInWhenHisSessionIsPersistedInCookie()
    {
        $this->assertFalse($this->customerSession->isLoggedIn());

        $this->setPersistentLoginCookie();
        $this->dispatch('customer/account/login');

        $this->assertTrue($this->customerSession->isLoggedIn());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Persistent/_files/persistent_with_customer_quote_and_cookie.php
     * @magentoConfigFixture current_store persistent/options/enabled 1
     */
    public function testItRedirectsUserWhenObserverLogsUserIn()
    {
        $this->assertFalse($this->customerSession->isLoggedIn());

        $this->setPersistentLoginCookie();
        $this->dispatch('customer/account/login');

        $this->assertRedirect();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Persistent/_files/persistent_with_customer_quote_and_cookie.php
     * @magentoConfigFixture current_store persistent/options/enabled 1
     */
    public function testItDoesNotLogCustomerWhenCookieIsNotThere()
    {
        $this->assertFalse($this->customerSession->isLoggedIn());

        $this->removePersistentCookie();
        $this->dispatch('customer/account/login');

        $this->assertFalse($this->customerSession->isLoggedIn());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Persistent/_files/persistent_with_customer_quote_and_cookie.php
     * @magentoConfigFixture current_store persistent/options/enabled 0
     */
    public function testItDoesNotLogCustomerWhenFeatureIsDisabled()
    {
        $this->assertFalse($this->customerSession->isLoggedIn());

        $this->dispatch('customer/account/login');

        $this->assertFalse($this->customerSession->isLoggedIn());
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
}

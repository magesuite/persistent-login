<?php

namespace MageSuite\PersistentLogin\Test\Integration\Observer;

/**
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class LogOutCustomerWhenTokenDoesNotMatchTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected const FIXTURE_CUSTOMER_EMAIL = 'customer@example.com';
    protected const CUSTOMER_ID = 1;

    protected ?\Magento\TestFramework\ObjectManager $objectManager;

    protected ?\Magento\Customer\Model\Session $customerSession;

    protected ?\Magento\Persistent\Helper\Session $persistenSessionHelper;

    protected ?\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;

    protected $persistentSessionFactory;

    protected ?\Magento\Framework\App\ResourceConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();

        $this->persistenSessionHelper = $this->objectManager->get(\Magento\Persistent\Helper\Session::class);
        $this->customerSession = $this->objectManager->create(\Magento\Customer\Model\Session::class);
        $this->customerRepository = $this->objectManager->get(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        $this->persistentSessionFactory = $this->objectManager->get(\Magento\Persistent\Model\SessionFactory::class);
        $this->connection = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);
    }

    /**
     * @magentoConfigFixture current_store persistent/options/enabled 1
     * @magentoDataFixture MageSuite_PersistentLogin::Test/Integration/_files/persistent_with_customer_quote_and_cookie_before_migration.php
     */
    public function testItDoesNotRemoveTokenFromDatabaseWhenGeneratedCorrectly()
    {
        $keyInCookie = 'de8d828f14f22750a0d007c1a3afc2cf2f8fb21b63b71c64633f2fb8d0a090a6';
        $expectedKeyInDatabase = 'f8075031ddd589109879650af93566b381bb53290aa11297cbcd4e824a44ffba';

        $generateHashKeyMock = $this->getMockBuilder(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class)
            ->disableOriginalConstructor()
            ->getMock();

        // we need a constant value that does not depend on crypt key (random on every test execution)
        $generateHashKeyMock->method('execute')->willReturn($keyInCookie);

        $this->objectManager->removeSharedInstance(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class);
        $this->objectManager->addSharedInstance(
            $generateHashKeyMock,
            \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class
        );

        $this->customerSession->loginById(self::CUSTOMER_ID);

        $this->assertTrue($this->customerSession->isLoggedIn());
        $this->assertEquals($expectedKeyInDatabase, $this->getPersistentSessionModel()->getKey());

        $this->setCookieValueAndRefreshPersistenSession($keyInCookie);
        $this->dispatch('customer/account/login');

        $this->assertTrue($this->customerSession->isLoggedIn());
        $this->assertNotNull($this->getPersistentSessionModel()->getKey());

        $this->setCookieValueAndRefreshPersistenSession('incorrect_value');
        $this->dispatch('customer/account/login');

        $this->assertFalse($this->customerSession->isLoggedIn());
        $this->assertNotNull($this->getPersistentSessionModel()->getKey());
    }

    /**
     * @magentoConfigFixture current_store persistent/options/enabled 1
     * @magentoDataFixture MageSuite_PersistentLogin::Test/Integration/_files/persistent_with_customer_quote_and_cookie_before_migration.php
     */
    public function testItDoesNotRemoveTokenFromDatabaseWhenGeneratedIncorrectly()
    {
        $keyInCookie = 'de8d828f14f22750a0d007c1a3afc2cf2f8fb21b63b71c64633f2fb8d0a090a6';
        $expectedKeyInDatabase = 'f8075031ddd589109879650af93566b381bb53290aa11297cbcd4e824a44ffba';

        $generateHashKeyMock = $this->getMockBuilder(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class)
            ->disableOriginalConstructor()
            ->getMock();

        // we need a constant value that does not depend on crypt key (random on every test execution)
        $generateHashKeyMock->method('execute')->willReturn($keyInCookie);

        $this->objectManager->removeSharedInstance(\MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class);
        $this->objectManager->addSharedInstance(
            $generateHashKeyMock,
            \MageSuite\PersistentLogin\Model\GenerateHashBasedOnCustomerData::class
        );

        $this->customerSession->loginById(1);

        $this->assertTrue($this->customerSession->isLoggedIn());
        $this->assertEquals($expectedKeyInDatabase, $this->getPersistentSessionModel()->getKey());

        $this->setCookieValueAndRefreshPersistenSession('incorrect_value');

        $incorrectKey = 'incorrect_key';
        $this->setIncorrectKeyInPersistentSessionInDatabase($incorrectKey);
        $this->assertEquals($incorrectKey, $this->getPersistentSessionModel()->getKey());

        $this->dispatch('customer/account/login');

        $this->assertFalse($this->customerSession->isLoggedIn());
        $this->assertNull($this->getPersistentSessionModel()->getKey());
    }

    protected function setCookieValueAndRefreshPersistenSession($cookieValue, $key = null): void
    {
        $_COOKIE[\Magento\Persistent\Model\Session::COOKIE_NAME] = $cookieValue; // phpcs:ignore
        $this->persistenSessionHelper->setSession(null);
        $this->persistenSessionHelper->getSession();
    }

    protected function setIncorrectKeyInPersistentSessionInDatabase($incorrectKey)
    {
        $connection = $this->connection->getConnection();
        $connection->update(
            'persistent_session',
            [
                'key' => $incorrectKey
            ],
            ['customer_id = ?' => self::CUSTOMER_ID]
        );
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

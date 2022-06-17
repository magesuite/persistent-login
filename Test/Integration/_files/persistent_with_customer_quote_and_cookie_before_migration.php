<?php
declare(strict_types=1);

\Magento\TestFramework\Workaround\Override\Fixture\Resolver::getInstance()
    ->requireDataFixture('Magento/Checkout/_files/quote_with_customer_without_address.php');

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var \Magento\Persistent\Model\SessionFactory $persistentSessionFactory */
$persistentSessionFactory = $objectManager->get(\Magento\Persistent\Model\SessionFactory::class);
$session = $persistentSessionFactory->create();
$session->setCustomerId(1)
    ->save();
$session->setKey('f495be79bad3d692686f63d43283c1f8f495be79bad3d69268')
    ->save();

$session->setPersistentCookie(10000, '');

<?php

namespace Alchemy\Tests\Phrasea\Controller\User;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class NotificationsTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::listNotifications
     */
    public function testListNotifications()
    {
        $this->XMLHTTPRequest('GET', '/user/notifications/');
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::listNotifications
     */
    public function testListNotificationsNoXMLHTTPRequests()
    {
        self::$DI['client']->request('GET', '/user/notifications/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::setNotificationsReaded
     */
    public function testSetNotificationsReadedNoXMLHTTPRequests()
    {
        self::$DI['client']->request('POST', '/user/notifications/read/');

        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::setNotificationsReaded
     */
    public function testSetNotificationsReaded()
    {
        $this->XMLHTTPRequest('POST', '/user/notifications/read/', [
            'notifications' => ''
        ]);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success'], $response->getContent());
        $this->assertArrayHasKey('message', $datas);
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\User\Notifications::connect
     * @covers Alchemy\Phrasea\Controller\User\Notifications::call
     */
    public function testRequireAuthentication()
    {
        self::$DI['app']['authentication']->setUser($this->getMockBuilder('Alchemy\Phrasea\Model\Entities\User')
            ->setMethods(['isGuest'])
            ->disableOriginalConstructor()
            ->getMock());

        self::$DI['app']['authentication']->getUser()->expects($this->once())
            ->method('isGuest')
            ->will($this->returnValue(true));

        self::$DI['client']->request('GET', '/user/notifications/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }
}

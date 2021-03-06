<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\ControllerProvider\Api\V1;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;

/**
 * @group functional
 * @group legacy
 */
class ApiAccountManipulatorTest extends \PhraseanetTestCase
{
    /** @var  ApiAccountManipulator */
    private $sut;

    public function setUp()
    {
        parent::setUp();

        $this->sut = new ApiAccountManipulator(self::$DI['app']['orm.em']);
    }

    public function testCreate()
    {
        $nbApps = count(self::$DI['app']['repo.api-accounts']->findAll());
        $account = $this->sut->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $this->assertGreaterThan($nbApps, count(self::$DI['app']['repo.api-accounts']->findAll()));
        $this->assertFalse($account->isRevoked());
        $this->assertEquals(V1::VERSION, $account->getApiVersion());
        $this->assertGreaterThan($nbApps, count(self::$DI['app']['repo.api-accounts']->findAll()));
    }

    public function testDelete()
    {
        $account = $this->sut->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $accountMem = clone $account;
        $countBefore = count(self::$DI['app']['repo.api-accounts']->findAll());
        self::$DI['app']['manipulator.api-oauth-token']->create($account);
        $this->sut->delete($account);
        $this->assertGreaterThan(count(self::$DI['app']['repo.api-accounts']->findAll()), $countBefore);
        $tokens = self::$DI['app']['repo.api-oauth-tokens']->findOauthTokens($accountMem);
        $this->assertEquals(0, count($tokens));
    }

    public function testUpdate()
    {
        $account = $this->sut->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $account->setApiVersion(24);
        $this->sut->update($account);
        $account = self::$DI['app']['repo.api-accounts']->find($account->getId());
        $this->assertEquals(24, $account->getApiVersion());
    }

    public function testAuthorizeAccess()
    {
        $account = $this->sut->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $this->sut->authorizeAccess($account);
        $this->assertFalse($account->isRevoked());
    }

    public function testRevokeAccess()
    {
        $account = $this->sut->create(self::$DI['oauth2-app-user'], self::$DI['user']);
        $this->sut->revokeAccess($account);
        $this->assertTrue($account->isRevoked());
    }
}

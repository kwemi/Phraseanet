<?php

namespace Alchemy\Tests\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Notification\Mail\MailSuccessEmailUpdate
 */
class MailSuccessEmailUpdateTest extends MailTestCase
{
    public function getMail()
    {
        return MailSuccessEmailUpdate::create(
            $this->getApp(),
            $this->getReceiverMock(),
            $this->getEmitterMock(),
            $this->getMessage()
        );
    }
}

<?php

namespace Tests\Suite;

use Sterzik\DI\DI;

class BasicDITest extends AbstractTestCase
{
    public function testBasicDI(): void
    {
        $di = $this->createDI();
        $this->assertInstanceof(DI::class, $di);
    }
}

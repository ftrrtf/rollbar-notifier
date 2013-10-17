<?php

namespace spec\Ftrrtf;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class RollbarSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        \Ftrrtf\Rollbar::init(array('access_token' => 'token'), false, false);
    }
}
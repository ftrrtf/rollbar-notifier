<?php

namespace spec\Ftrrtf\Rollbar\Adapter;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CurlSpec extends ObjectBehavior
{
    public function let()
    {
        $apiUrl = 'rollbar_api';
        $timeout = 3;

        $this->beConstructedWith(
            $apiUrl,
            $timeout
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Adapter\Curl');
        $this->shouldImplement('Ftrrtf\Rollbar\Adapter\AdapterInterface');
    }

    public function it_send_logs_to_api()
    {
        $this->send(array());
    }
}

<?php

namespace spec\Ftrrtf\Rollbar\Transport;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CurlSpec extends ObjectBehavior
{
    function let()
    {
        $accessToken = 'abc';
        $apiUrl      = 'rollbar_api';
        $timeout     = 3;

        $this->beConstructedWith(
            $accessToken,
            $apiUrl,
            $timeout
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Transport\Curl');
        $this->shouldImplement('Ftrrtf\Rollbar\Transport\TransportInterface');
    }

    function it_send_logs_to_api()
    {
        $this->send(array());
    }
}

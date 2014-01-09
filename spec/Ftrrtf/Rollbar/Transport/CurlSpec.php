<?php

namespace spec\Ftrrtf\Rollbar\Transport;

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
        $this->shouldHaveType('Ftrrtf\Rollbar\Transport\Curl');
        $this->shouldImplement('Ftrrtf\Rollbar\Transport\TransportInterface');
    }

    public function it_send_logs_to_api()
    {
        $this->send(array());
    }
}

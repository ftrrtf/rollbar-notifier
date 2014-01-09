<?php

namespace spec\Ftrrtf\Rollbar;

use Ftrrtf\Rollbar\Environment;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EnvironmentSpec extends ObjectBehavior
{
    protected $options = array(
        'host' => 'test_host',
        'branch' => 'test_branch',
        'root' => 'test_root'
    );

    public function let()
    {
        $this->beConstructedWith($this->options);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Environment');
    }

    public function it_build_request_data()
    {
        $_SERVER['REQUEST_METHOD'] = 'method';
        $_SERVER['SERVER_NAME'] = 'host';
        $_SERVER['SERVER_PORT'] = 100;
        $_SERVER['REQUEST_URI'] = '/test';

        $_SERVER['HTTP_KEY'] = 'header';
        $_GET = array('get_key' => 'get_value');
        $_POST = array('post_key' => 'post_value');
        $_SESSION = array('session_key' => 'session_value');

        $this->getRequestData()->shouldBeLike(
            array(
                'url' => "http://host:100/test",
                'headers' => array(
                    'Key' => 'header'
                ),
                'method' => $_SERVER['REQUEST_METHOD'],
                'GET' => $_GET,
                'POST' => $_POST,
                'session' => $_SESSION,
            )
        );
    }

    public function it_get_http_headers()
    {
        $_SERVER  = array(
            'HTTP_KEY' => 'value1',
            'NO_HTTP_KEY' => 'value2'
        );

        $this->getHeaders()->shouldReturn(
            array(
                'Key' => 'value1'
            )
        );
    }

    public function it_get_current_url()
    {
        $_SERVER  = array(
            'HTTPS' => 'on',
            'SERVER_NAME' => 'server_name',
            'SERVER_PORT' => 100,
            'REQUEST_URI' => '/test'
        );

        $this->getCurrentUrl()->shouldReturn('https://server_name:100/test');
    }

    public function it_get_framework()
    {
        $this->beConstructedWith(array('framework' => 'framework 1.5'));
        $this->getFramework()->shouldReturn('framework 1.5');
    }

    public function it_is_get_custom_data()
    {
//        $this->getCustomData();
    }

    public function it_get_user_ip()
    {
        $_SERVER  = array(
            'HTTP_X_FORWARDED_FOR' => '0.0.0.0',
            'HTTP_X_REAL_IP' => '0.0.0.1',
            'REMOTE_ADDR' => '0.0.0.2',
        );
        $this->getUserIp()->shouldReturn('0.0.0.0');

        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $this->getUserIp()->shouldReturn('0.0.0.1');

        unset($_SERVER['HTTP_X_REAL_IP']);
        $this->getUserIp()->shouldReturn('0.0.0.2');
    }

    public function it_get_server_data()
    {
        $this->getServerData()->shouldReturn($this->options);
    }

    public function it_get_person_data()
    {
        $this->getPersonData();
    }

    public function it_set_get_person_callback()
    {
        $this->setOption('personFn', function() {
            return null;
        });
    }

    function letgo()
    {
        $_SESSION = array();
        $_POST    = array();
        $_GET     = array();
        $_SERVER  = array();
    }
}

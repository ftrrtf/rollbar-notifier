<?php

namespace spec\Ftrrtf\Rollbar;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EnvironmentSpec extends ObjectBehavior
{
    protected $options = array(
        'host'     => 'test_host',
        'branch'   => 'test_branch',
        'root_dir' => 'test_root_dir'
    );

    function let()
    {
        $this->beConstructedWith($this->options);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Environment');
    }

    function it_builds_request_data()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR']    = '1.1.1.1';
        $_SERVER['SERVER_NAME']    = 'host';
        $_SERVER['SERVER_PORT']    = 100;
        $_SERVER['REQUEST_URI']    = '/test';
        $_SERVER['HTTP_KEY']       = 'header';

        $_GET     = array('get_key' => 'get_value');
        $_POST    = array('post_key' => 'post_value');
        $_SESSION = array('session_key' => 'session_value');

        $this->getRequestData()->shouldBeLike(
            array(
                'url'     => "http://host:100/test",
                'user_ip' => "1.1.1.1",
                'method'  => 'GET',
                'headers' => array(
                    'Key' => 'header'
                ),
                'GET'     => $_GET,
                'POST'    => $_POST,
                'session' => $_SESSION,
            )
        );
    }

    function it_gets_http_headers()
    {
        $_SERVER = array(
            'HTTP_KEY'    => 'value1',
            'NO_HTTP_KEY' => 'value2'
        );

        $this->getHeaders()->shouldReturn(
            array(
                'Key' => 'value1'
            )
        );
    }

    function it_gets_current_url()
    {
        $_SERVER = array(
            'HTTPS'       => 'on',
            'SERVER_NAME' => 'server_name',
            'SERVER_PORT' => 100,
            'REQUEST_URI' => '/test'
        );

        $this->getCurrentUrl()->shouldReturn('https://server_name:100/test');
    }

    function it_gets_framework()
    {
        $this->beConstructedWith(array('framework' => 'framework 1.5'));
        $this->getFramework()->shouldReturn('framework 1.5');
    }

    function it_gets_user_ip()
    {
        $_SERVER = array(
            'HTTP_X_FORWARDED_FOR' => '0.0.0.0',
            'HTTP_X_REAL_IP'       => '0.0.0.1',
            'REMOTE_ADDR'          => '0.0.0.2',
        );
        $this->getUserIp()->shouldReturn('0.0.0.0');

        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $this->getUserIp()->shouldReturn('0.0.0.1');

        unset($_SERVER['HTTP_X_REAL_IP']);
        $this->getUserIp()->shouldReturn('0.0.0.2');
    }

    function it_gets_server_data()
    {
        $this->getServerData()->shouldReturn(
            array(
                'host'   => 'test_host',
                'branch' => 'test_branch',
                'root'   => 'test_root_dir'
            )
        );
    }

    function it_gets_custom_data()
    {
        $this->getCustomData()->shouldReturn(array());

        $_SERVER['REQUEST_TIME_FLOAT'] = 123;

        $this->getCustomData()->shouldHaveKey('runtime');
    }

    function it_gets_person_data()
    {
        $this->getPersonData();
    }

    function it_sets_callback_for_getting_person_data()
    {
        $this->setOption(
            'person_callback',
            function () {
                return null;
            }
        );
    }

    function letgo()
    {
        $_SESSION = array();
        $_POST    = array();
        $_GET     = array();
        $_SERVER  = array();
    }

    public function getMatchers()
    {
        return array(
            'haveKey' => function($subject, $key) {
                return array_key_exists($key, $subject);
            }
        );
    }
}

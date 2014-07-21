<?php

namespace spec\Ftrrtf\Rollbar\Report;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PhpErrorSpec extends ObjectBehavior
{
    function let()
    {
        $errorLevel = E_NOTICE;
        $errorMessage = "Some notice message";
        $errorFile = 'file.php';
        $errorLine = '10';
        $this->beConstructedWith($errorLevel, $errorMessage, $errorFile, $errorLine);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Report\PhpError');
        $this->shouldImplement('Ftrrtf\Rollbar\Report\ReportInterface');
    }

    function it_get_rollbar_error_level_by_php_error_level()
    {
        $this->getErrorLevel(E_NOTICE)->shouldReturn('info');
        $this->getErrorLevel(E_ERROR)->shouldReturn('error');
        $this->getErrorLevel(E_DEPRECATED)->shouldReturn('info');
    }

    function it_get_php_error_constant_name_by_php_error_level()
    {
        $this->getPhpConstant(E_ERROR)->shouldReturn('E_ERROR');
        $this->getPhpConstant(E_WARNING)->shouldReturn('E_WARNING');
        $this->getPhpConstant(E_PARSE)->shouldReturn('E_PARSE');
        $this->getPhpConstant(E_NOTICE)->shouldReturn('E_NOTICE');
        $this->getPhpConstant(E_CORE_ERROR)->shouldReturn('E_CORE_ERROR');
        $this->getPhpConstant(E_CORE_WARNING)->shouldReturn('E_CORE_WARNING');
        $this->getPhpConstant(E_COMPILE_ERROR)->shouldReturn('E_COMPILE_ERROR');
        $this->getPhpConstant(E_COMPILE_WARNING)->shouldReturn('E_COMPILE_WARNING');
        $this->getPhpConstant(E_USER_ERROR)->shouldReturn('E_USER_ERROR');
        $this->getPhpConstant(E_USER_WARNING)->shouldReturn('E_USER_WARNING');
        $this->getPhpConstant(E_USER_NOTICE)->shouldReturn('E_USER_NOTICE');
        $this->getPhpConstant(E_STRICT)->shouldReturn('E_STRICT');
        $this->getPhpConstant(E_RECOVERABLE_ERROR)->shouldReturn('E_RECOVERABLE_ERROR');
        $this->getPhpConstant(E_DEPRECATED)->shouldReturn('E_DEPRECATED');
        $this->getPhpConstant(E_USER_DEPRECATED)->shouldReturn('E_USER_DEPRECATED');
    }
}

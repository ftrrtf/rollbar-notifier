<?php

namespace Ftrrtf\Rollbar;

use Ftrrtf\Rollbar\Transport\TransportInterface;
use Ftrrtf\Rollbar\Transport\Curl;
use Ftrrtf\Rollbar\Report;
use Ftrrtf\Rollbar\Report\ReportInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class Notifier
 *
 * @package Ftrrtf\Rollbar
 */
class Notifier
{
    const VERSION = '0.1';
    const ROLLBAR_CLIENT_COMPATIBILITY_VERSION = "0.5.5";

    // ignore E_STRICT and above
//    protected $maxErrno = E_USER_NOTICE;


//    protected $captureErrorBacktraces = true;

    /**
     * @var array
     */
    protected $options;

    protected $requiredOptions = array(
        'access_token'
    );

    /**
     * @var array
     */
    protected $errorSampleRates = array();

    /**
     * @var array payload queue, used when $batched is true
     */
    protected $queue = array();

    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var Environment
     */
    protected $environment = null;

    /**
     * @var TransportInterface
     */
    protected $transport;

    protected $mtRandmax;

    /**
     * @param Environment $environment
     * @param array       $options
     */
    public function __construct(Environment $environment, $options)
    {
        $this->environment = $environment;

        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function setOption($option, $value)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults($this->options);
        $resolver->setRequired($this->requiredOptions);
        $this->options = $resolver->resolve(array($option => $value));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'batched' => true,
                'batch_size' => 50,
//                'capture_error_backtraces',
//                'error_sample_rates',
//                'logger',
//                'max_errno',
            )
        );

        $resolver->setRequired($this->requiredOptions);
    }

    public function setTransport(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @return TransportInterface
     */
    public function getTransport()
    {
        // Default transport
        if (is_null($this->transport)) {
            $this->setTransport(new Curl());
        }

        return $this->transport;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function reportException($exception)
    {
        try {
            return $this->report(
                new Report\Exception($exception)
            );
        } catch (\Exception $e) {
            try {
                $this->logError("Exception while reporting exception");
            } catch (\Exception $e) {
                // swallow
            }
        }
    }

    public function reportMessage($message, $level = 'error', $extraData = null)
    {
        try {
            return $this->report(
                new Report\Message($message, $extraData),
                $level
            );
        } catch (\Exception $e) {
            try {
                $this->logError("Exception while reporting message");
            } catch (\Exception $e) {
                // swallow
            }
        }
    }

    public function reportBacktraceMessage($backtrace, $message, $level = 'error', $extraData = null)
    {
        try {
            return $this->report(
                new Report\Backtrace($backtrace, $message, $extraData),
                $level
            );
        } catch (\Exception $e) {
            try {
                $this->logError("Exception while reporting message");
            } catch (\Exception $e) {
                // swallow
            }
        }
    }

    public function reportPhpError($errorLevel, $errorMessage, $errorFile, $errorLine)
    {

//        // fill in missing values in error_sample_rates
//        $levels = array(
//            E_WARNING,
//            E_NOTICE,
//            E_USER_ERROR,
//            E_USER_WARNING,
//            E_USER_NOTICE,
//            E_STRICT,
//            E_RECOVERABLE_ERROR
//        );
//
//        // PHP 5.3.0
//        if (defined('E_DEPRECATED')) {
//            $levels = array_merge($levels, array(E_DEPRECATED, E_USER_DEPRECATED));
//        }
//
//        $current = 1;
//        for ($i = 0, $num = count($levels); $i < $num; $i++) {
//            $level = $levels[$i];
//            if (isset($this->errorSampleRates[$level])) {
//                $current = $this->errorSampleRates[$level];
//            } else {
//                $this->errorSampleRates[$level] = $current;
//            }
//        }
//
//        // cache this value
//        $this->mtRandmax = mt_getrandmax();

        try {
//            if ($this->maxErrno != -1 && $errno >= $this->maxErrno) {
//                // ignore
//                return;
//            }
//
//            if (isset($this->errorSampleRates[$errno])) {
//                // get a float in the range [0, 1)
//                // mt_rand() is inclusive, so add 1 to mt_randmax
//                $float_rand = mt_rand() / ($this->mtRandmax + 1);
//                if ($float_rand > $this->errorSampleRates[$errno]) {
//                    // skip
//                    return;
//                }
//            }


            return $this->report(
                new Report\PhpError($errorLevel, $errorMessage, $errorFile, $errorLine),
                Report\PhpError::getErrorLevel($errorLevel)
            );
        } catch (\Exception $e) {
            try {
                $this->logError("Exception while reporting php error");
            } catch (\Exception $e) {
                // swallow
            }
        }
    }

    /**
     * Flushes the queue.
     * Called internally when the queue exceeds $batch_size, and by Rollbar::flush
     * on shutdown.
     */
    public function flush()
    {
        $queueSize = count($this->queue);
        if (!$this->options['batched'] || $queueSize > 0) {
            $this->logInfo('Flushing queue of size ' . $queueSize);
            $this->getTransport()->send($this->queue);
            $this->queue = array();
        }
    }

    protected function report(ReportInterface $report, $level = null)
    {
        $data = $this->buildBaseData();

        $data['body'][$report->getType()] = $report->getReport();

        if (!is_null($level)) {
            $data['level'] = strtolower($level);
        }

        $data['request'] = $this->environment->getRequestData();
        $data['server'] = $this->environment->getServerData();
        $data['person'] = $this->environment->getPersonData();
        $data['custom'] = $this->environment->getCustomData();

        $payload = $this->buildPayload($data);
        $this->sendPayload($payload);

        return $data['uuid'];
    }

    protected function buildBaseData($level = 'error')
    {
        return array(
            'environment' => $this->environment->getEnvironment(),
            'code_version' => $this->environment->getCodeVersion(),
            'framework' => $this->environment->getFramework(),
            'timestamp' => time(),
            'language' => 'php ' . PHP_VERSION,
            'level' => $level,
            'notifier' => array(
                'name' => 'ftrrtf-rollbar-notifier',
                'version' => self::VERSION,
                'compatibility' => self::ROLLBAR_CLIENT_COMPATIBILITY_VERSION,
            ),
            'uuid' => $this->generateUUID4()
        );

    }

    protected function buildPayload($data)
    {
        return array(
            'access_token' => $this->options['access_token'],
            'data' => $data
        );
    }

    protected function sendPayload($payload)
    {
        $this->queue[] = $payload;

        if (!$this->options['batched'] || count($this->queue) >= $this->options['batch_size']) {
            $this->flush();
        }
    }

    /**
     * from http://www.php.net/manual/en/function.uniqid.php#94959
     *
     * @return string
     */
    protected function generateUUID4()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /* Logging */

    protected function logInfo($msg)
    {
        $this->logMessage("INFO", $msg);
    }

    protected function logWarning($msg)
    {
        $this->logMessage("WARNING", $msg);
    }

    protected function logError($msg)
    {
        $this->logMessage("ERROR", $msg);
    }

    protected function logMessage($level, $msg)
    {
        if ($this->logger !== null) {
            $this->logger->log($level, $msg);
        }
    }
}

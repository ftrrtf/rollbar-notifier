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
    const VERSION = '1.0';
    const ROLLBAR_CLIENT_COMPATIBILITY_VERSION = "0.9.6";

    /**
     * @var array
     */
    protected $options;

    /**
     * @var bool
     */
    protected $iconvAvailable;

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

    /**
     * @param Environment        $environment
     * @param TransportInterface $transport
     * @param array              $options
     */
    public function __construct(Environment $environment, TransportInterface $transport, $options = array())
    {
        $this->environment = $environment;
        $this->transport = $transport;

        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function setOption($option, $value)
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults($this->options);
        $this->options = $resolver->resolve(array($option => $value));
    }

    /**
     * @return Environment
     */
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
        if (error_reporting() === 0 && !$this->options['report_suppressed']) {

            // ignore
            return false;
        }

        try {
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
     * Called internally when the queue exceeds batch_size, and by flush
     * on shutdown.
     */
    public function flush()
    {
        $queueSize = count($this->queue);
        if ($queueSize > 0) {
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

        $this->sanitizeUTF8($data);

        $payload = $this->buildPayload($data);
        $this->sendPayload($payload);

        return $data['uuid'];
    }

    /**
     * Sanitize non utf-8 values
     *
     * @param $data
     */
    protected function sanitizeUTF8(&$data) {
        if (!isset($this->iconvAvailable)) {
            $this->iconvAvailable = function_exists('iconv');
        }

        if ($this->iconvAvailable) {
            array_walk_recursive(
                $data,
                function(&$value) {
                    if (is_string($value)) {
                        $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
                    }
                }
            );
        }
    }

    protected function buildBaseData($level = 'error')
    {
        return array(
            'environment' => $this->environment->getEnvironment(),
            'code_version' => $this->environment->getCodeVersion(),
            'framework' => $this->environment->getFramework(),
            'timestamp' => time(),
            'language' => 'php',
            'language_version' => PHP_VERSION,
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

    /**
     * @return TransportInterface
     */
    protected function getTransport()
    {
        return $this->transport;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'batched' => false,
                'batch_size' => 50,
                'report_suppressed' => false
//                'logger',
            )
        );
    }
}

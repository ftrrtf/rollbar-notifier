<?php

namespace Ftrrtf\Rollbar;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class Request
 *
 * @package Ftrrtf\Rollbar
 */
class Environment
{
    // Cached values for request/server/person data
    protected $requestData = null;
    protected $serverData = null;
    protected $personData = null;
    protected $options;
    protected $requiredOptions = array();


    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
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
     * @return array|null
     */
    public function getRequestData()
    {
        if ($this->requestData === null) {
            if (!is_null($currentUrl = $this->getCurrentUrl())) {
                $this->requestData['url'] = $currentUrl;
            }

            if (!is_null($userIP = $this->getUserIP())) {
                $this->requestData['user_ip'] = $userIP;
            }

            if (isset($_SERVER['REQUEST_METHOD'])) {
                $this->requestData['method'] = $_SERVER['REQUEST_METHOD'];
            }

            if (count($headers = $this->getHeaders()) > 0) {
                $this->requestData['headers'] = $headers;
            }

            if ($this->requestData['method'] == 'PUT') {
                parse_str(file_get_contents("php://input"), $this->requestData['PUT']);
            }

            if ($this->requestData['method'] == 'DELETE') {
                parse_str(file_get_contents("php://input"), $this->requestData['DELETE']);
            }

            if ($_GET) {
                $this->requestData['GET'] = $_GET;
            }

            if ($_POST) {
                $this->requestData['POST'] = $this->scrubRequestParams($_POST);
            }

            if (isset($_SESSION) && $_SESSION) {
                $this->requestData['session'] = $this->scrubRequestParams($_SESSION);
            }
        }

        return $this->requestData;
    }


    public function getHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $key => $val) {
            if (substr($key, 0, 5) == 'HTTP_') {
                // convert HTTP_CONTENT_TYPE to Content-Type, HTTP_HOST to Host, etc.
                $name = strtolower(substr($key, 5));
                if (strpos($name, '_') != -1) {
                    $name = preg_replace('/ /', '-', ucwords(preg_replace('/_/', ' ', $name)));
                } else {
                    $name = ucfirst($name);
                }
                $headers[$name] = $val;
            }
        }

        return $headers;
    }


    public function getCurrentUrl()
    {
        if (!isset($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'], $_SERVER['REQUEST_URI'])) {
            return null;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
        } else if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } else if (!empty($_SERVER['HTTP_HOST'])) {
            $parts = explode(':', $_SERVER['HTTP_HOST']);
            $host = $parts[0];
        } else if (!empty($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        } else {
            $host = 'unknown';
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $port = $_SERVER['HTTP_X_FORWARDED_PORT'];
        } else if (!empty($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        } else {
            $port = 80;
        }

        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        $url = $protocol . '://' . $host;

        if (($protocol == 'https' && $port != 443) || ($protocol == 'http' && $port != 80)) {
            $url .= ':' . $port;
        }

        $url .= $path;

        return $url;
    }

    public function getUserIP()
    {
        $forwardFor = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null;
        if ($forwardFor) {
            // return everything until the first comma
            $parts = explode(',', $forwardFor);
            return $parts[0];
        }
        $realIP = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : null;
        if ($realIP) {
            return $realIP;
        }

        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }


    /**
     * @return null|array
     */
    public function getServerData()
    {
        if ($this->serverData === null) {
            $this->serverData['host'] = $this->options['host'];
            $this->serverData['branch'] = $this->options['branch'];
            $this->serverData['root'] = $this->options['root_dir'];

            if (isset($_SERVER['USER'])) {
                $this->serverData['user'] = $_SERVER['USER'];
            }

            if (isset($_SERVER['SHELL'])) {
                $this->serverData['shell'] = $_SERVER['SHELL'];
            }

            if (isset($_SERVER['argv'], $_SERVER['argc'])) {
                $args = $_SERVER['argv'];
                $this->serverData['cli']['file'] = array_shift($args);
                $this->serverData['cli']['args'] = implode(' ', $args);
            }

        }

        return $this->serverData;
    }


    public function getPersonData()
    {
        // return cached value if non-null
        // it *is* possible for it to really be null (i.e. user is not logged in)
        // but we'll keep trying anyway until we get a logged-in user value.
        if ($this->personData == null) {
            // first priority: try to use $this->person
            if (!empty($this->options['person'])) {
                if (isset($this->options['person']['id'])) {
                    $this->personData = $this->options['person'];
                    return $this->personData;
                }
            }

            // second priority: try to use $this->person_fn
            if ($this->options['person_callback'] && is_callable($this->options['person_callback'])) {
                $data = @call_user_func($this->options['person_callback']);
                if (isset($data['id'])) {
                    $this->personData = $data;
                    return $this->personData;
                }
            }
        }

        return $this->personData;
    }

    public function scrubRequestParams($params)
    {
        $scrubbed = array();
        foreach ($params as $k => $v) {
            if (in_array($k, $this->options['scrub_fields'])) {
                $count = is_array($v) ? count($v) : strlen($v);
                $scrubbed[$k] = str_repeat('*', $count);
            } else {
                $scrubbed[$k] = $v;
            }
        }

        return $scrubbed;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->options['environment'];
    }

    /**
     * @return null
     */
    public function getCodeVersion()
    {
        return $this->options['code_version'];
    }

    public function getFramework()
    {
        return $this->options['framework'];
    }

    public function getCustomData()
    {
        $custom = array();

        $startRequestTime = isset($_SERVER['REQUEST_TIME_FLOAT'])
            ? $_SERVER['REQUEST_TIME_FLOAT']
            : (isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : false);

        if ($startRequestTime) {
            $custom['runtime'] = microtime(true) - $startRequestTime;
        }

        return $custom;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    protected function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'code_version' => null,
                'branch' => 'master',
                'environment' => 'production',
                'root_dir' => null,
                'framework' => null,
                'host' => function_exists('gethostname') ? gethostname() : php_uname('n'),
                'person' => array(),
                'person_callback' => null,
                'scrub_fields' => array(
                    'passwd',
                    'pass',
                    'password',
                    'secret',
                    'confirm_password',
                    'password_confirmation',
                    'auth_token',
                    'csrf_token'
                ),
            )
        );

        $resolver->setAllowedTypes(
            array(
                'scrub_fields' => 'array',
            )
        );

        $resolver->setRequired($this->requiredOptions);
    }
}

<?php

namespace Ftrrtf\Rollbar\Adapter;

class Curl implements AdapterInterface
{
    protected $timeout;
    protected $baseApiUrl;

    public function __construct($baseApiUrl = 'https://api.rollbar.com/api/1/', $timeout = 3)
    {
        $this->baseApiUrl = $baseApiUrl;
        $this->timeout = $timeout;
    }

    public function send($items)
    {
        $postData = json_encode($items);
        $url = $this->baseApiUrl . 'item_batch' . '/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $result = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        //        if ($statusCode != 200) {
        //            $this->logWarning(
        //                'Got unexpected status code from Rollbar API ' . $action . ': ' .$statusCode
        //            );
        //            $this->logWarning('Output: ' .$result);
        //        } else {
        //            $this->logInfo('Success');
        //        }
    }
}

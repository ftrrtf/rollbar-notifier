<?php

namespace Ftrrtf\Rollbar\Transport;

class Curl implements TransportInterface
{
    protected $accessToken;
    protected $baseApiUrl;
    protected $timeout;

    /**
     * @param string $accessToken
     * @param string $baseApiUrl
     * @param int    $timeout
     */
    public function __construct($accessToken, $baseApiUrl = 'https://api.rollbar.com/api/1/', $timeout = 3)
    {
        $this->accessToken = $accessToken;
        $this->baseApiUrl  = $baseApiUrl;
        $this->timeout     = $timeout;
    }

    /**
     * @param $items
     */
    public function send($items)
    {
        $itemsCount = count($items);

        if ($itemsCount == 0) {
            return;
        }

        foreach ($items as &$item) {
            $item['access_token'] = $this->accessToken;
        }
        unset($item);


        if ($itemsCount == 1) {
            return $this->apiCall('item', json_encode(array_pop($items)));
        }

        if ($itemsCount > 1) {
            return $this->apiCall('item_batch', json_encode($items));
        }
    }

    /**
     * @param $action
     * @param $postData
     */
    protected function apiCall($action, $postData)
    {
        $url = $this->baseApiUrl . $action . '/';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Rollbar-Access-Token: ' . $this->accessToken));

        $result     = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err        = curl_errno($ch);
        $errmsg     = curl_error($ch);
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

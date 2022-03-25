<?php

namespace App\Api\MyMiniFactory;

/**
 * This API class is loosely based on the Thingiverse API wrapper (\App\Api\Thingiverse)
 */
class MyMiniFactory {
    const BASE_URL = 'https://www.myminifactory.com/api/v2/';
    public string $access_token;


    public function __construct($token) {
        // Optional, if you already have your valid token. Otherwise, call oAuth().
        $this->access_token = $token;
    }

    public function getObject($id) {
        $url = self::BASE_URL . 'objects/' . $id;

        return $this->_send($url);
    }

    protected function _send($url, $type = 'GET', $post_params = null) {
        if (empty($this->access_token))
            exit('No access token.');
        if (empty($url))
            exit('No URL.');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url . "?key=" . $this->access_token);

        $type = strtoupper($type);
        switch ($type) {
            case 'POST'  :
            case 'PATCH' :
            case 'DELETE':
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_params));
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
            case 'GET':
                break;
            default:
                exit("Invalid request type: '$type'.");
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        $response = curl_exec($curl);
        $this->response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $response_header = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);

        if ($this->response_code != 200) {
            if (preg_match('/x-error: (.+)/i', $response_header, $match))
                $this->last_response_error = $match[1];
            else
                $this->last_response_error = 'No error given in header. Check response body.';
        } else
            $this->last_response_error = '';

        // Uncomment next four lines to see/debug full cURL response
        // $curl_info = curl_getinfo($curl);
        // var_dump($curl_info);
        // var_dump($response_header);
        // var_dump($response_body);

        curl_close($curl);

        // $this->_reset();

        return json_decode($response_body);
    }
}

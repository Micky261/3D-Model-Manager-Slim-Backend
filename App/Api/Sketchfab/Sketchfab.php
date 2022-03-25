<?php

namespace App\Api\Sketchfab;

/**
 * This API class is loosely based on the Thingiverse API wrapper (\App\Api\Thingiverse)
 */
class Sketchfab {
    const BASE_URL = 'https://api.sketchfab.com/v3/';
    public string $personal_token;


    public function __construct($personal_token = "") {
        $this->personal_token = $personal_token;
    }

    public function getModel($id) {
        $url = self::BASE_URL . 'models/' . $id;

        return $this->_send($url);
    }

    public function getModelFiles($id) {
        $url = self::BASE_URL . 'models/' . $id . "/download";

        return $this->_send($url, true);
    }

    protected function _send($url, $needs_auth = false, $type = 'GET', $post_params = null) {
        if ($needs_auth && empty($this->personal_token))
            exit('No personal token.');
        if (empty($url))
            exit('No URL.');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url . "?key=" . $this->personal_token);

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

        if ($needs_auth) {
            $header = array();
            $header[] = "Authorization: Token " . $this->personal_token;
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
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

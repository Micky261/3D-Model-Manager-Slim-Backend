<?php

namespace App\Api\Cults3D;

/**
 * This API class is loosely based on the Thingiverse API wrapper (\App\Api\Thingiverse)
 */
class Cults3D {
    const GRAPHQL_URL = "https://cults3d.com/graphql";
    private string $objectQuery = "{\"query\":\"{ creation(slug: \\\"%s\\\") { name(locale: EN) description(locale: EN) creator { nick } license { name(locale: EN) } tags(locale: EN) url(locale: EN) illustrations { imageUrl } blueprints { imageUrl fileUrl fileExtension } } } \",\"variables\":null}";

    public string $username;
    public string $password;

    public function __construct(string $username, string $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function getObject($slug) {
        $url = self::GRAPHQL_URL;
        $post_params = sprintf($this->objectQuery, $slug);
        $post_params = json_decode($post_params);

        return $this->_send($url, "POST", $post_params);
    }

    protected function _send($url, $type = 'GET', $post_params = null) {
        if (empty($url))
            exit('No URL.');

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);

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
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_USERPWD, "$this->username:$this->password");

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

        // Uncomment next lines to see/debug full cURL response
//        $curl_info = curl_getinfo($curl);
//        var_dump($curl_info);
//        echo "\n\n\n";
//        var_dump($response_header);
//        echo "\n\n\n";
//        var_dump($response_body);
//        echo "\n\n\n";

        curl_close($curl);

        // $this->_reset();

        return json_decode($response_body);
    }
}

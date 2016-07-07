<?php

namespace Novanova\Odnoklassniki;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Odnoklassniki
 * @package Novanova\Odnoklassniki
 */
class Odnoklassniki
{

    /**
     * @var string
     */
    private $app_id;
    /**
     * @var string
     */
    private $public_key;
    /**
     * @var string
     */
    private $secret_session_key;
    /**
     * @var string
     */
    private $access_token;

    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @param $app_id
     * @param $public_key
     * @param $secret
     */
    public function __construct($app_id, $public_key, $secret_session_key, $access_token)
    {
        $this->app_id = $app_id;
        $this->public_key = $public_key;
        $this->secret_session_key = $secret_session_key;
        $this->access_token = $access_token;

        $this->guzzle = new Client();
    }

    /**
     * @return string
     */
    public function app_id()
    {
        return $this->app_id;
    }

    /**
     * @return string
     */
    public function public_key()
    {
        return $this->public_key;
    }


    /**
     * @param $method
     * @param $params
     * @param $access_token
     * @return mixed
     * @throws OdnoklassnikiException
     */
    public function api($method, array $params = array())
    {
        $params['application_key'] = $this->public_key;
        $params['method'] = $method;
        $params['format'] = 'json';
        $params['sig'] = $this->sign($params);
        $params['access_token'] = $this->access_token;

        return $this->call('https//api.ok.ru/fb.do', $params);
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     * @throws OdnoklassnikiException
     */
    public function promo_api($method, array $params = array())
    {
        $params['appId'] = $this->app_id;
        $params['format'] = 'json';
        $params['sig'] = $this->sign($params);

        return $this->call('http://sp.odnoklassniki.ru/projects/common/' . $method, $params);
    }

    /**
     * @param $params
     * @param $access_token
     * @return string
     */
    public function sign(array $params, $access_token = null)
    {
        $sign = '';
        ksort($params);
        foreach ($params as $key => $value) {
            if ('sig' == $key || 'resig' == $key || 'access_token' == $key) {
                continue;
            }
            $sign .= $key . '=' . $value;
        }

        return md5($sign . $this->secret_session_key);
    }

    /**
     * @param $params
     * @return mixed
     * @throws OdnoklassnikiException
     */
    private function call($url, array $params)
    {
        try {
            $response = $this->guzzle->post(
                $url,
                array(
                    'body' => $params
                )
            )->getBody();
        } catch (RequestException $e) {
            throw new OdnoklassnikiException($e->getMessage());
        }

        if (!$response = json_decode($response)) {
            throw new OdnoklassnikiException('Response parse error');
        }

        if (!empty($response->error_code) && !empty($response->error_msg)) {
            throw new OdnoklassnikiException($response->error_msg, $response->error_code);
        }

        return $response;
    }
}

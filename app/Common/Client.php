<?php


namespace App\Common;


use GuzzleHttp\Exception\GuzzleException;

class Client
{
    public static function getInstance()
    {
        static $instance = null;
        if ($instance == null)
            $instance = new Client();
        return $instance;
    }

    public function getBody($uri, $method = 'GET', $options = [])
    {
        $proxies = [
            'http://46.225.237.174:8080',
            'http://109.238.190.147:8080',
            'http://185.18.212.227:3128',
            'http://178.252.179.13:8080',
            'http://178.252.179.14:8080',
            'http://178.252.179.11:8080',
            'http://46.209.247.20:8080',
        ];
        $client = new \GuzzleHttp\Client();
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36'
        ];
        if ($method === 'POST') {
            $headers['Content-Type'] = 'multipart/form-data';
        }
        try {
            $response = $client->request($method, $uri, array_merge($options, [
                'headers' => $headers,
                'proxy' => [
                    'https' => $proxies[array_rand($proxies)],
                    'http' => $proxies[array_rand($proxies)]
                ]
            ]));
            dump(true);
            return (string)$response->getBody();
        } catch (GuzzleException $e) {
            dump($e->getMessage());
            return false;
        }
    }
}
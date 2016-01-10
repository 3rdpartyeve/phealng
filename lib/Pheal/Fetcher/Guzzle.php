<?php
/*
 MIT License
 Copyright (c) 2010 - 2016 Daniel Hoffend, Peter Petermann

 Permission is hereby granted, free of charge, to any person
 obtaining a copy of this software and associated documentation
 files (the "Software"), to deal in the Software without
 restriction, including without limitation the rights to use,
 copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the
 Software is furnished to do so, subject to the following
 conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 OTHER DEALINGS IN THE SOFTWARE.
*/
namespace Pheal\Fetcher;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Pheal\Core\Config;
use Pheal\Exceptions\ConnectionException;
use Pheal\Exceptions\HTTPException;
use Pheal\Pheal;


/**
 * @author Kevin Mauel <kevin.mauel2@gmail.com>
 */
class Guzzle implements CanFetch
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * Set the actual config instance, based on it creates a client instance
     */
    private function init()
    {
        if(!isset($this->config)) {
            $this->config = Config::getInstance();
            $this->client = new Client(
                $this->generateClientConfiguration()
            );
        }
    }

    /**
     * Generates Client configuration based on current config instance
     * @return array
     */
    private function generateClientConfiguration()
    {
        $clientConfiguration = [
            'base_uri' => $this->config->api_base,
            'timeout' => $this->config->http_timeout,
            'headers' => [
                'Connection' => 'keep-alive',
                'Accept-Encoding' => ''
            ]
        ];

        if ($this->config->http_user_agent !== false) {
            $clientConfiguration['headers']['User-Agent'] =
                'PhealNG/' . Pheal::VERSION . ' ' . $this->config->http_user_agent;
        }

        if ($this->config->http_keepalive !== false) {
            $clientConfiguration['headers']['Keep-Alive'] = 'timeout=' .
            $this->config->http_keepalive === true ? 15 : $this->config->http_keepalive .
                ', max=1000';
        }

        $clientConfiguration['verify'] = false;

        if ($this->config->http_ssl_verifypeer === true && $this->config->http_ssl_certificate_file !== false) {
            $clientConfiguration['verify'] = $this->config->http_ssl_certificate_file;
        } elseif ($this->config->http_ssl_verifypeer === true) {
            $clientConfiguration['verify'] = true;
        }

        return $clientConfiguration;
    }

    /**
     * Fetches data from api
     * @param string $url
     * @param array $options
     * @throws ConnectionException
     * @throws HTTPException
     * @return string
     */
    public function fetch($url, $options)
    {
        $this->init();

        $request_type = $this->config->http_post === true ?
            'form_params' : 'query';
        $options = [$request_type => $options];

        try {
            $response = $this->client->request(
                $this->config->http_post === true ? 'POST' : 'GET',
                $url,
                $options
            );
        } catch (GuzzleException $exception) {
            throw new ConnectionException(
                $exception->getMessage(),
                $exception->getCode()
            );
        }

        if ($response->getStatusCode() >= 400) {
            // ccp is using error codes even if they send a valid application
            // error response now, so we have to use the content as result
            // for some of the errors. This will actually break if CCP ever uses
            // the HTTP Status for an actual transport related error.
            switch ($response->getStatusCode()) {
                case 400:
                case 403:
                case 500:
                case 503:
                    return $response->getBody()->getContents();
                    break;
            }

            throw new HTTPException(
                $response->getStatusCode(),
                $url
            );
        }

        return $response->getBody()->getContents();
    }
}

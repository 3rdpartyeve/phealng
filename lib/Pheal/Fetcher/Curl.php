<?php
/*
 MIT License
 Copyright (c) 2010 - 2014 Daniel Hoffend, Peter Petermann

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

use Pheal\Core\Config;
use Pheal\Exceptions\ConnectionException;
use Pheal\Exceptions\HTTPException;
use Pheal\Pheal;

class Curl implements CanFetch
{
    /**
     * resource handler for curl
     * @static
     * @var resource
     */
    public static $curl;

    /**
     * static method to close open http connections.
     * example: force closing keep-alive connections that are no longer needed.
     * @static
     * @return void
     */
    public static function disconnect()
    {
        if (is_resource(self::$curl) && get_resource_type(self::$curl) == 'curl') {
            curl_close(self::$curl);
        }
        self::$curl = null;
    }

    /**
     * method will do the actual http call using curl libary.
     * you can choose between POST/GET via config.
     * will throw Exception if http request/curl times out or fails
     * @param String $url url beeing requested
     * @param array $opts an array of query paramters
     * @throws \Pheal\Exceptions\ConnectionException
     * @throws \Pheal\Exceptions\HTTPException
     * @return string raw http response
     */
    public function fetch($url, $opts)
    {
        // init curl
        if (!(is_resource(self::$curl) && get_resource_type(self::$curl) == 'curl')) {
            self::$curl = curl_init();
        }

        // custom user agent
        curl_setopt(
            self::$curl,
            CURLOPT_USERAGENT,
            "PhealNG/" . Pheal::VERSION . ' ' . Config::getInstance()->http_user_agent
        );

        // custom outgoing ip address
        if (($http_interface_ip = Config::getInstance()->http_interface_ip) != false) {
            curl_setopt(self::$curl, CURLOPT_INTERFACE, $http_interface_ip);
        }

        // ignore ssl peer verification if needed
        if (substr($url, 0, 5) == "https") {
            curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, Config::getInstance()->http_ssl_verifypeer);

            if (Config::getInstance()->http_ssl_verifypeer
                && (Config::getInstance()->http_ssl_certificate_file !== false)) {
                curl_setopt(self::$curl, CURLOPT_CAINFO, Config::getInstance()->http_ssl_certificate_file);
            }
        }

        // http timeout
        if (($http_timeout = Config::getInstance()->http_timeout) != false) {
            curl_setopt(self::$curl, CURLOPT_TIMEOUT, $http_timeout);
        }

        // use post for params
        if (count($opts) && Config::getInstance()->http_post) {
            curl_setopt(self::$curl, CURLOPT_POST, true);
            curl_setopt(self::$curl, CURLOPT_POSTFIELDS, $opts);
        } else {
            curl_setopt(self::$curl, CURLOPT_POST, false);

            // attach url parameters
            if (count($opts)) {
                $url .= "?" . http_build_query($opts, '', '&');
            }
        }

        // additional headers
        $headers = array();

        // enable/disable keepalive
        if (($http_keepalive = Config::getInstance()->http_keepalive) != false) {
            curl_setopt(self::$curl, CURLOPT_FORBID_REUSE, false);
            $http_keepalive = ($http_keepalive === true) ? 15 : (int)$http_keepalive;
            $headers[] = "Connection: keep-alive";
            $headers[] = "Keep-Alive: timeout=" . $http_keepalive . ", max=1000";
        } else {
            curl_setopt(self::$curl, CURLOPT_FORBID_REUSE, true);
        }

        // allow all encodings
        curl_setopt(self::$curl, CURLOPT_ENCODING, "");

        // curl defaults
        curl_setopt(self::$curl, CURLOPT_URL, $url);
        curl_setopt(self::$curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);

        // call
        $result = curl_exec(self::$curl);
        $errno = curl_errno(self::$curl);
        $error = curl_error(self::$curl);

        // response http headers
        $httpCode = curl_getinfo(self::$curl, CURLINFO_HTTP_CODE);

        if (!Config::getInstance()->http_keepalive) {
            self::disconnect();
        }

        // http errors
        if ($httpCode >= 400) {
            // ccp is using error codes even if they send a valid application
            // error response now, so we have to use the content as result
            // for some of the errors. This will actually break if CCP ever uses
            // the HTTP Status for an actual transport related error.
            switch($httpCode) {
                case 400:
                case 403:
                case 500:
                case 503:
                    return $result;
                    break;
                default:
            }
            throw new HTTPException($httpCode, $url);
        }

        // curl errors
        if ($errno) {
            throw new ConnectionException($error, $errno);
        } else {
            return $result;
        }

    }
}

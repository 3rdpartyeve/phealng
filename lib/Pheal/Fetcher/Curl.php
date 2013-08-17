<?php

namespace Pheal\Fetcher;

use Pheal\Core\Config;
use Pheal\Exceptions\ConnectionException;
use Pheal\Exceptions\HTTPException;

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
        if (($http_user_agent = Config::getInstance()->http_user_agent) != false) {
            curl_setopt(self::$curl, CURLOPT_USERAGENT, $http_user_agent);
        }

        // custom outgoing ip address
        if (($http_interface_ip = Config::getInstance()->http_interface_ip) != false) {
            curl_setopt(self::$curl, CURLOPT_INTERFACE, $http_interface_ip);
        }

        // ignore ssl peer verification if needed
        if (substr($url, 0, 5) == "https") {
            curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, Config::getInstance()->http_ssl_verifypeer);
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

<?php
/*
 MIT License
 Copyright (c) 2010 - 2013 Peter Petermann, Daniel Hoffend

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
namespace Pheal\Core;

/**
 * PhealConfig, implementing Singleton this is meant to
 * store all Library configuration, like cache etc.
 * to change the default config you can for example do:
 * PhealConfig::getInstance()->cache = new CacheObject();
 */
class Config
{
    /**
     * Cache Object, defaults to an \Pheal\Cache\NullStorage Object
     * @var \Pheal\Cache\CanCache
     */
    public $cache;

    /**
     * Archive Object, defaults to an \Pheal\Archive\NullStorage Object
     * @var \Pheal\Archive\CanArchive
     */
    public $archive;

    /**
     * Access Object to validate and check an API with a given keyType+accessMask
     * defaults to \Pheal\Access\NullCheck
     * @var \Pheal\Access\CanCheck
     */
    public $access;

    /**
     * Fetcher object to decide what technology is to be used to fetch
     * defaults to \Pheal\Fetcher\Curl
     * @var \Pheal\Fetcher\CanFetch
     */
    public $fetcher;

    /**
     * usually this points to the EVE API directly, however if you use a API
     * proxy you might want to modify this.
     * use https://api.eveonline.com/ if you like to have ssl support
     * @var String
     */
    public $api_base = "https://api.eveonline.com/";

    /**
     * enable the new customize key system (use keyID instead of userID, etc)
     * @var bool
     */
    public $api_customkeys = true;

    /**
     * associative array with additional parameters that should be passed
     * to the API on every request.
     * @var array
     */
    public $additional_request_parameters = array();

    /**
     * which outgoing ip/inteface should be used for the http request
     * (bool) false means use default ip address
     * @var String
     */
    public $http_interface_ip = false;

    /**
     * which useragent should be used for http calls.
     * (bool) false means do not change php default
     * @var String
     */
    public $http_user_agent = false;

    /**
     * should parameters be transfered in the POST body request or via GET request
     * @var bool
     */
    public $http_post = false;

    /**
     * After what time should an api call considered to as timeout?
     * @var int
     */
    public $http_timeout = 20;

    /**
     * Verify the SSL peer?
     * You may need to provide a bundle of trusted Certificate Agencyies
     *
     * @see self::$http_ssl_certificate_file
     * @see CURLOPT_SSL_VERIFYPEER
     *
     * @var bool
     */
    public $http_ssl_verifypeer = true;

    /**
     * If you want to verify the SSL connections to the EVE API, you may need to provide a bundle of
     * trusted certification agencies
     *
     * @see CURLOPT_CAINFO
     * @see http://curl.haxx.se/ca/cacert.pem
     *
     * @var string|false
     */
    public $http_ssl_certificate_file = false;

    /**
     * reuse a http connection (keep-alive for X seconds) to lower the connection handling overhead
     * keep in mind after the script ended the connection will be closed anyway.
     *
     * @var bool|int number of seconds a connection should be kept open (bool true == 15)
     */
    public $http_keepalive = false;

    /**
     * Log object to log and measure the API calls that were made
     * defaults to \Pheal\Log\NullStorage (== no logging)
     *
     * @var \Pheal\Log\CanLog
     */
    public $log;

    /**
     * Rate limiter object to avoid exceeding CCP-defined maximum requests per second.
     * Defaults to \Pheal\RateLimiter\NullRateLimiter (== no rate limiting)
     *
     * @var \Pheal\RateLimiter\CanRateLimit
     */
    public $rateLimiter;

    /**
     * private constructor (use getInstance() to get an Instance)
     */
    private function __construct()
    {
        $this->cache = new \Pheal\Cache\NullStorage();
        $this->archive = new \Pheal\Archive\NullStorage();
        $this->log = new \Pheal\Log\NullStorage();
        $this->access = new \Pheal\Access\NullCheck();
        $this->fetcher = new \Pheal\Fetcher\Curl();
        $this->rateLimiter = new \Pheal\RateLimiter\NullRateLimiter();

        $this->http_user_agent = "( Unknown PHP Application )";
    }

    /**
     * Return Instance of PhealConfig Object
     * @staticvar null|\static $instance Singleton Instance
     * @return Config|null
     */
    public static function getInstance()
    {
        static $instance = null;

        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }
}

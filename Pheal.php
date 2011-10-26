<?php
/*
 MIT License
 Copyright (c) 2010 Peter Petermann, Daniel Hoffend

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

/**
 * Pheal (PHp Eve Api Library), a EAAL Port for PHP
 */
class Pheal
{
    /**
     * Version container
     */
    public static $version = "0.1.0";

    /**
     * resource handler for curl
     * @static
     * @var resource
     */
    public static $curl;

    /**
     * @var int
     */
    private $userid;

    /**
     * @var string
     */
    private $key;

    /**
     * var @string|null
     */
    private $keyType;
    
    /**
     * @var int
     */
    private $accessMask;

    /**
     * EVE Api scope to be used (for example: "account", "char","corp"...)
     * @var string
     */
    public $scope;

    /**
     * Result of the last XML request, so application can use the raw xml data
     * @var String 
     */
    public $xml;

    /**
     * creates new Pheal API object
     * @param int $userid the EVE userid/keyID
     * @param string $key the EVE apikey/vCode
     * @param string $scope scope to use, defaults to account. scope can be changed during usage by modifycation of public attribute "scope"
     */
    public function __construct($userid=null, $key=null, $scope="account")
    {
        $this->userid = $userid;
        $this->key = $key;
        $this->scope = $scope;
    }

    /**
     * Magic __call method, will translate all function calls to object to API requests
     * @param String $name name of the function
     * @param array $arguments an array of arguments
     * @return PhealResult
     */
    public function  __call($name, $arguments)
    {
        if(count($arguments) < 1 || !is_array($arguments[0]))
            $arguments[0] = array();
        $scope = $this->scope;
        return $this->request_xml($scope, $name, $arguments[0]); // we only use the
        //first argument params need to be passed as an array, due to naming

    }

    /**
     * Magic __get method used to set scope
     * @param string $name name of the scope e.g. "mapScope"
     * @return mixed Pheal or null
     */
    public function __get($name)
    {
        if (preg_match('/(.+)Scope$/', $name, $matches) == 1) {
            $this->scope = $matches[1];
            return $this;
        }
        return null;
    }

    /**
     * Set keyType/accessMask
     * @param string $keyType   must be Account/Character/Corporation or null
     * @param int $accessMask   must be integer or 0
     * @return void
     */
    public function setAccess($keyType=null, $accessMask=0)
    {
        $this->keyType = in_array(ucfirst(strtolower($keyType)),array('Account','Character','Corporation')) ? $keyType : null;
        $this->accessMask = (int)$accessMask;
    }

    /**
     * clear+reset keyType/accessMask
     * @return void
     */
    public function clearAccess()
    {
        $this->setAccess();
    }

    /**
     * if userid+key is given it automatically detects (api call) the keyinfo and
     * set the correct access level for futher checks.
     *
     * Keep in mind this method will make an api request to account/APIKeyInfo based
     * on the given PhealConfig settings with the given key credentials.
     *
     * More important! This method will throw Exceptions on invalid keys or networks errors
     * So place this call into your try statement
     *
     * @throws PhealException|PhealAPIException|PhealHTTPException
     * @return bool|PhealResult
     */
    public function detectAccess()
    {
        // don't request keyinfo if api keys are not set or if new CAK aren't enabled
        if(!$this->userid || !$this->key || !PhealConfig::getInstance()->api_customkeys)
            return false;

        // request api key info, save old scope and restore it afterwords
        $old = $this->scope;
        $this->scope = "account";
        $keyinfo = $this->APIKeyInfo();
        $this->scope = $old;

        // set detected keytype and accessMask
        $this->setAccess($keyinfo->key->type, $keyinfo->key->accessMask);

        // return the APIKeyInfo Result object in the case you need it.
        return $keyinfo;
    }

    /**
     * method will ask caching class for valid xml, if non valid available
     * will make API call, and return the appropriate result
     * @throws PhealException|PhealAPIException|PhealHTTPException|PhealAccessException
     * @param string $scope api scope (examples: eve, map, server, ...)
     * @param string $name  api method (examples: ServerStatus, Kills, Sovereignty, ...)
     * @param array $opts   additional arguments (example: characterID => 12345, ...), should not contain apikey/userid/keyid/vcode
     * @return PhealResult
     */
    private function request_xml($scope, $name, array $opts = array())
    {
        $opts = array_merge(PhealConfig::getInstance()->additional_request_parameters, $opts);

        // apikey/userid/keyid|vcode shouldn't be allowed in arguments and removed to avoid wrong cached api calls
        foreach($opts AS $k => $v) {
            if(in_array(strtolower($k), array('userid','apikey','keyid','vcode')))
                unset($opts[$k]);
        }

        // prepare http arguments + url (to not modify original argument list for cache saving)
        $url = PhealConfig::getInstance()->api_base . $scope . '/' . $name . ".xml.aspx";
        $use_customkey = (bool)PhealConfig::getInstance()->api_customkeys;
        $http_opts = $opts;
        if($this->userid) $http_opts[($use_customkey?'keyID':'userid')] = $this->userid;
        if($this->key) $http_opts[($use_customkey?'vCode':'apikey')] = $this->key;

        // check access level if given (throws PhealAccessExpception if API call is not allowed)
        if($use_customkey && $this->userid && $this->key && $this->keyType) {
            try {
                PhealConfig::getInstance()->access->check($scope,$name,$this->keyType,$this->accessMask);
            } catch (Exception $e) {
                PhealConfig::getInstance()->log->errorLog($scope,$name,$http_opts,$e->getMessage());
                throw $e;
            }
        }

        // check cache first
        if(!$this->xml = PhealConfig::getInstance()->cache->load($this->userid,$this->key,$scope,$name,$opts))
        {
            try {
                // start measure the response time
                PhealConfig::getInstance()->log->start();

                // request
                if(PhealConfig::getInstance()->http_method == "curl" && function_exists('curl_init'))
                    $this->xml = self::request_http_curl($url,$http_opts);
                else
                    $this->xml = self::request_http_file($url,$http_opts);

                // stop measure the response time
                PhealConfig::getInstance()->log->stop();

                // parse
                $element = new SimpleXMLElement($this->xml);

            // just forward HTTP Errors
            } catch(PhealHTTPException $e) {
                throw $e;

            // other request errors
            } catch(Exception $e) {
                // log + throw error
                PhealConfig::getInstance()->log->errorLog($scope,$name,$http_opts,$e->getCode() . ': ' . $e->getMessage());
                throw new PhealException('API Date could not be read / parsed, original exception: ' . $e->getMessage());
            }
            PhealConfig::getInstance()->cache->save($this->userid,$this->key,$scope,$name,$opts,$this->xml);
            
            // archive+save only non-error api calls + logging
            if(!$element->error) {
                PhealConfig::getInstance()->log->log($scope,$name,$http_opts);
                PhealConfig::getInstance()->archive->save($this->userid,$this->key,$scope,$name,$opts,$this->xml);
            } else {
                PhealConfig::getInstance()->log->errorLog($scope,$name,$http_opts,$element->error['code'] . ': ' . $element->error);
            }
        } else {
            $element = new SimpleXMLElement($this->xml);
        }
        return new PhealResult($element);
    }

    /**
     * method will do the actual http call using curl libary. 
     * you can choose between POST/GET via config.
     * will throw Exception if http request/curl times out or fails
     * @param String $url url beeing requested
     * @param array $opts an array of query paramters
     * @return string raw http response
     */
    public static function request_http_curl($url,$opts)
    {
        // init curl
        if(!(is_resource(self::$curl) && get_resource_type(self::$curl) == 'curl'))
            self::$curl = curl_init();

        // custom user agent
        if(($http_user_agent = PhealConfig::getInstance()->http_user_agent) != false)
            curl_setopt(self::$curl, CURLOPT_USERAGENT, $http_user_agent);
        
        // custom outgoing ip address
        if(($http_interface_ip = PhealConfig::getInstance()->http_interface_ip) != false)
            curl_setopt(self::$curl, CURLOPT_INTERFACE, $http_interface_ip);

        // ignore ssl peer verification if needed
        if(substr($url,5) == "https")
            curl_setopt(self::$curl, CURLOPT_SSL_VERIFYPEER, PhealConfig::getInstance()->http_ssl_verifypeer);
            
        // http timeout 
        if(($http_timeout = PhealConfig::getInstance()->http_timeout) != false)
            curl_setopt(self::$curl, CURLOPT_TIMEOUT, $http_timeout);
            
        // use post for params
        if(count($opts) && PhealConfig::getInstance()->http_post)
        {
            curl_setopt(self::$curl, CURLOPT_POST, true);
            curl_setopt(self::$curl, CURLOPT_POSTFIELDS, $opts);
        }
        else
        {
            curl_setopt(self::$curl, CURLOPT_POST, false);
            
            // attach url parameters
            if(count($opts))
            $url .= "?" . http_build_query($opts);
        }
        
        // additional headers
        $headers = array();
        
        // enable/disable keepalive
        if(($http_keepalive = PhealConfig::getInstance()->http_keepalive) != false)
        {
            curl_setopt(self::$curl, CURLOPT_FORBID_REUSE, false);
            $http_keepalive = ($http_keepalive === true) ? 15 : (int)$http_keepalive;
            $headers[] = "Connection: keep-alive";
            $headers[] = "Keep-Alive: timeout=" . $http_keepalive . ", max=1000";
        }
        else 
        {
            curl_setopt(self::$curl, CURLOPT_FORBID_REUSE, true);
        }

        // allow all encodings
        curl_setopt(self::$curl, CURLOPT_ENCODING, "");

        // curl defaults
        curl_setopt(self::$curl, CURLOPT_URL, $url);
        curl_setopt(self::$curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
        
        // call
        $result	= curl_exec(self::$curl);
        $errno = curl_errno(self::$curl);
        $error = curl_error(self::$curl);

        // response http headers
        $httpCode = curl_getinfo(self::$curl, CURLINFO_HTTP_CODE);

        if(!PhealConfig::getInstance()->http_keepalive)
            self::disconnect();

        // http errors
        if($httpCode >= 400)
            throw new PhealHTTPException($httpCode, $url);

        // curl errors
        if($errno)
            throw new Exception($error, $errno);
        else
            return $result;
    }
    
    /**
     * method will do the actual http call using file()
     * remember: on some installations, file_get_contents(url) might not be available due to
     * restrictions via allow_url_fopen
     * @param String $url url beeing requested
     * @param array $opts an array of query paramters
     * @return string raw http response
     */
    public static function request_http_file($url,$opts)
    {
        $options = array();
        
        // set custom user agent
        if(($http_user_agent = PhealConfig::getInstance()->http_user_agent) != false)
            $options['http']['user_agent'] = $http_user_agent;
        
        // set custom http timeout
        if(($http_timeout = PhealConfig::getInstance()->http_timeout) != false)
            $options['http']['timeout'] = $http_timeout;
        
        // ignore ssl peer verification if needed
        if(substr($url,5) == "https")
            $options['ssl']['verify_peer'] = PhealConfig::getInstance()->http_ssl_verifypeer;
        
        // use post for params
        if(count($opts) && PhealConfig::getInstance()->http_post)
        {
            $options['http']['method'] = 'POST';
            $options['http']['content'] = http_build_query($opts);
        }
        // else build url parameters
        elseif(count($opts))
        {
            $url .= "?" . http_build_query($opts);
        }

        // set track errors. needed for $php_errormsg
        $oldTrackErrors = ini_get('track_errors');
        ini_set('track_errors', true);

        // create context with options and request api call
        // suppress the 'warning' message which we'll catch later with $php_errormsg
        if(count($options)) 
        {
            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);
        } else {
            $result = @file_get_contents($url);
        }
        
        // check for http errors via magic $http_response_header
        $httpCode = 200;
        if(isset($http_response_header[0]))
            list($httpVersion,$httpCode,$httpMsg) = explode(' ', $http_response_header[0], 3);
        
        // throw http error
        if(is_numeric($httpCode) && $httpCode >= 400)
            throw new PhealHTTPException($httpCode, $url);

        // throw error
        if($result === false) {
            $message = ($php_errormsg ? $php_errormsg : 'HTTP Request Failed');
            
            // set track_errors back to the old value
            ini_set('track_errors',$oldTrackErrors);

            throw new Exception($message);

        // return result
        } else {
            // set track_errors back to the old value
            ini_set('track_errors',$oldTrackErrors);
            return $result;
        }
    }

    /**
     * static method to close open http connections.
     * example: force closing keep-alive connections that are no longer needed.
     * @static
     * @return void
     */
    public static function disconnect()
    {
        if(is_resource(self::$curl) && get_resource_type(self::$curl) == 'curl')
            curl_close(self::$curl);
        self::$curl = null;
    }
    
    /**
     * static method to use with spl_autoload_register
     * for usage include Pheal.php and then spl_autoload_register("Pheal::classload");
     * @param String $name
     * @return boolean
     */
    public static function classload($name)
    {
        $dir = pathinfo(__FILE__, PATHINFO_DIRNAME) ."/";
        if(substr($name, 0, 5) == "Pheal" && file_exists($dir . $name .".php"))
        {
            require_once($dir . $name . ".php");
            return true;
        }
        return false;
    }
}


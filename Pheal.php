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
    public static $version = "0.0.11";

    /**
     * @var int
     */
    private $userid;

    /**
     * @var string
     */
    private $key;

    /**
     * EVE Api scope to be used (for example: "account", "char","corp"...)
     * @var String 
     */
    public $scope;
    
    /**
     * Result of the last XML request, so application can use the raw xml data
     * @var String 
     */
    public $xml;

    /**
     * creates new Pheal API object
     * @param int $userid the EVE userid
     * @param string $key the EVE apikey
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
     * method will ask caching class for valid xml, if non valid available
     * will make API call, and return the appropriate result
     * @todo errorhandling
     * @return PhealResult
     */
    private function request_xml($scope, $name, $opts)
    {
        $opts = array_merge(PhealConfig::getInstance()->additional_request_parameters, $opts);
        if(!$this->xml = PhealConfig::getInstance()->cache->load($this->userid,$this->key,$scope,$name,$opts))
        {
            $url = PhealConfig::getInstance()->api_base . $scope . '/' . $name . ".xml.aspx";
            if($this->userid) $opts['userid'] = $this->userid;
            if($this->key) $opts['apikey'] = $this->key;
            
            try {
                // start measure the response time
                PhealConfig::getInstance()->log->start();

                // request
                if(PhealConfig::getInstance()->http_method == "curl" && function_exists('curl_init'))
                    $this->xml = self::request_http_curl($url,$opts);
                else
                    $this->xml = self::request_http_file($url,$opts);

                // stop measure the response time
                PhealConfig::getInstance()->log->stop();

                // parse
                $element = new SimpleXMLElement($this->xml);

            } catch(Exception $e) {
                // log + throw error
                PhealConfig::getInstance()->log->errorLog($scope,$name,$opts,$e->getCode() . ': ' . $e->getMessage());
                throw new PhealException('API Date could not be read / parsed, orginial exception: ' . $e->getMessage());
            }
            PhealConfig::getInstance()->cache->save($this->userid,$this->key,$scope,$name,$opts,$this->xml);
            
            // archive+save only non-error api calls + logging
            if(!$element->error) {
                PhealConfig::getInstance()->log->log($scope,$name,$opts);
                PhealConfig::getInstance()->archive->save($this->userid,$this->key,$scope,$name,$opts,$this->xml);
            } else {
                PhealConfig::getInstance()->log->errorLog($scope,$name,$opts,$element->error['code'] . ': ' . $element->error);
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
        $curl = curl_init();

        // custom user agent
        if(($http_user_agent = PhealConfig::getInstance()->http_user_agent) != false)
            curl_setopt($curl, CURLOPT_USERAGENT, $http_user_agent);
        
        // custom outgoing ip address
        if(($http_interface_ip = PhealConfig::getInstance()->http_interface_ip) != false)
            curl_setopt($curl, CURLOPT_INTERFACE, $http_interface_ip);
            
        // use post for params
        if(count($opts) && PhealConfig::getInstance()->http_post)
        {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $opts);
        }
        // else build url parameters
        elseif(count($opts))
        {
            $url .= "?" . http_build_query($opts);
        }
        
        if(($http_timeout = PhealConfig::getInstance()->http_timeout) != false)
            curl_setopt($curl, CURLOPT_TIMEOUT, $http_timeout);
        
        // curl defaults
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");


        
        // call
        $result	= curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        curl_close($curl);

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


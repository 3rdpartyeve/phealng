<?php
/*
 MIT License
 Copyright (c) 2010 Peter Petermann

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
    public static $version = "0.0.1";

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
     * creates new Pheal API object
     * @param int $userid the EVE userid
     * @param string $key the EVE apikey
     * @param string $scope scope to use, defaults to account. scope can be changed during usage by modifycation of public attribute "scope"
     */
    public function __construct($userid, $key, $scope="account")
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
    public function  __call($name,  $arguments)
    {
        if(count($arguments) < 1) $arguments[0] = array();
        $scope = $this->scope;
        return $this->request_xml($scope, $name, $arguments[0]); // we only use the
        //first argument params need to be passed as an array, due to naming

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
       if(!$xml = PhealConfig::getInstance()->cache->load($this->userid,$this->key,$scope,$name,$opts))
        {
            $url = PhealConfig::getInstance()->api_base . $scope . '/' . $name . ".xml.aspx";
            $url .= "?userid=" . $this->userid . "&apikey=" . $this->key;
            foreach($opts as $name => $value)
            {
                $url .= "&" . $name . "=" . urlencode($value);
            }
            $xml = join('', file($url));
            PhealConfig::getInstance()->cache->save($this->userid,$this->key,$scope,$name,$opts,$xml);
        }
        return new PhealResult(new SimpleXMLElement($xml));
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


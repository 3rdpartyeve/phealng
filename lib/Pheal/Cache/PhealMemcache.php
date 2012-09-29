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
 * Implememnts memcached into Pheal
 */
class PhealMemcache implements PhealCacheInterface
{
    /**
     * active memcache instance/connection
     * @var Memcache
     */
    protected $memcache;

    /**
     * memcache options (connection)
     * @var array
     */
    protected $options = array(
        'host' => 'localhost',
        'port' => 11211,
    );

    /**
     * construct PhealMemcache,
     * @param array $options optional config array, valid keys are: host, port
     */
    public function __construct($options = array())
    {
        // add options
        if(is_array($options) && count($options))
            $this->options = array_merge($this->options, $options);

        $this->memcache = new Memcache();
        $this->memcache->connect($this->options['host'], $this->options['port']);
    }

    /**
     * create a memcache key (prepend Pheal_ to not conflict with other keys)
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return string
     */
    protected function getKey($userid, $apikey, $scope, $name, $args) 
    {
        $key = "$userid|$apikey|$scope|$name";
        foreach($args as $k=>$v) {
            if(!in_array(strtolower($key), array('userid','apikey','keyid','vcode')))
                $key  .= "|$k|$v";
        }
        return "Pheal_" . md5($key);
    }

    /**
     * Load XML from cache
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     */
    public function load($userid, $apikey, $scope, $name, $args)
    {
        $key = $this->getKey($userid, $apikey, $scope, $name, $args);
        return $this->memcache->get($key);
    }

    /**
     *  Return the number of seconds the XML is valid. Will never be less than 1.
     *  @return int
     */
    protected function getTimeout($xml)
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set("UTC");

        $xml = new SimpleXMLElement($xml);
        $dt = (int) strtotime($xml->cachedUntil);
        $time = time();

        date_default_timezone_set($tz);
        return max(1, $dt - $time);
    }

    /**
     * Save XML to cache
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @param string $xml
     */
    public function save($userid,$apikey,$scope,$name,$args,$xml)
    {
        $key = $this->getKey($userid, $apikey, $scope, $name, $args);
        $timeout = $this->getTimeout($xml);
        $this->memcache->set($key, $xml, 0, time() + $timeout);
    }
}

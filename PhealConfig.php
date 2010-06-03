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
 * PhealConfig, implementing Singleton this is meant to 
 * store all Library configuration, like cache etc.
 * to change the default config you can for example do:
 * PhealConfig::getInstance()->cache = new CacheObject();
 */
class PhealConfig
{
    /**
     * Cache Object, defaults to an PhealNullCache Object
     * @var PhealCacheInterface
     */
    public $cache;

    /**
     * usually this points to the EVE API directly, however if you use a API
     * proxy you might want to modify this.
     * @var String
     */
    public $api_base = "http://api.eve-online.com/";

    /**
     * associative array with additional parameters that should be passed
     * to the API on every request.
     * @var array
     */
    public $additional_request_parameters = array();

    /**
     * Singleton Instance
     * @var PhealConfig
     */
    private static $myInstance = null;

    /**
     * private constructor (use getInstance() to get an Instance)
     */
    private function __construct()
    {
        $this->cache = new PhealNullCache();
    }

    /**
     * Return Instance of PhealConfig Object
     * @return PhealConfig
     */
    public static function getInstance()
    {
        if(is_null(self::$myInstance))
            self::$myInstance = new PhealConfig();
        return self::$myInstance;
    }
}
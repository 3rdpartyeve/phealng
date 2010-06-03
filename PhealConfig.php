<?php
class PhealConfig
{
    public $cache;
    public $api_base = "http://api.eve-online.com/";
    public $additional_request_parameters = array();

    private static $myInstance = null;

    private function __construct()
    {
        $this->cache = new PhealNullCache();
    }

    /**
     *
     * @return PhealConfig
     */
    public static function getInstance()
    {
        if(is_null(self::$myInstance))
            self::$myInstance = new PhealConfig();
        return self::$myInstance;
    }
}
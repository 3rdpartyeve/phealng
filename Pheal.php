<?php
class Pheal
{
    private $userid;
    private $key;
    public $scope;
    
    public function __construct($userid, $key, $scope="account")
    {
        $this->userid = $userid;
        $this->key = $key;
        $this->scope = $scope;
    }

    public function  __call($name,  $arguments)
    {
        if(count($arguments) < 1) $arguments[0] = array();
        $scope = $this->scope;
        return $this->request_xml($scope, $name, $arguments[0]); // we only use the
        //first argument params need to be passed as an array, due to naming

    }

    private function request_xml($scope, $name, $opts)
    {
        
        $opts = array_merge(PhealConfig::getInstance()->additional_request_parameters, $opts);

        // @todo CACHE FOO here
        $url = PhealConfig::getInstance()->api_base . $scope . '/' . $name . ".xml.aspx";
        
        $url .= "?userid=" . $this->userid . "&apikey=" . $this->key;
        foreach($opts as $name => $value)
        {
            $url .= "&" . $name . "=" . urlencode($value);
        }
        $xml = join('', file($url));

        // @todo MORE CACHE FOO here
        
        return new PhealResult(new SimpleXMLElement($xml));
    }

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


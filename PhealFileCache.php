<?php
class PhealFileCache
{
    private $basepath;

    public function __construct($basepath = false)
    {
        if(!$basepath)
            $basepath = $_ENV["HOME"]. "/.pheal/cache/";
        $this->basepath = $basepath;
    }

    private function filename($userid, $apikey, $scope, $name, $args)
    {
        $argstr = "";
        foreach($args as $key => $val)
        {
            if(strlen($val) < 1)
                unset($args[$key]);
            else
                $argstr .= "$key:$val:";
        }
        $argstr = substr($argstr, 0, -1);
        $filename = "Request_" . $argstr . ".xml";
        $filepath = $this->basepath . "$userid/$apikey/$scope/$name/";
        if(!file_exists($filepath))
            mkdir($filepath, 0777, true);
        return $filepath . $filename;
    }

    public function load($userid, $apikey, $scope, $name, $args)
    {
        $filename = $this->filename($userid, $apikey, $scope, $name, $args);
        if(!file_exists($filename))
            return false;
        $xml = join('', file($filename));
        if($this->validate_cache($xml, $name))
            return $xml;
        return false;

    }

    public function validate_cache($xml, $name) // contains name hack for broken eve api
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set("UTC");

        $xml = new SimpleXMLElement($xml);
        $dt = date_parse($xml->cachedUntil);
        $dt = mktime($dt["hour"], $dt["minute"], $dt["second"], $dt["month"],$dt["day"], $dt["year"]);
        $time = time();
        date_default_timezone_set($tz);

        switch($name) //name hack!
        {  
            case "WalletJournal":
                if(($dt + 3600) > time())
                    return true;
                break;
            default:
                if($dt > $time)
                    return true;
        }
        return false;
    }

    public function save($userid,$apikey,$scope,$name,$args,$xml) 
    {
        $filename= $this->filename($userid, $apikey, $scope, $name, $args);
        $fp = fopen($filename, "w");
        fwrite($fp, $xml);
        fclose($fp);
    }
}

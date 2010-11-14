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
 * Simple filecache for the xml
 */
class PhealFileCache implements PhealCacheInterface
{
    /**
     * path where to store the xml
     * @var string
     */
    protected $basepath;

    /**
     * delimiter for arguments in the filename
     * @var string
     */
    protected $delimiter = ":";

    /**
     * construct PhealFileCache,
     * @param string $basepath optional string on where to store files, defaults to the current/users/home/.pheal/cache/
     */
    public function __construct($basepath = false)
    {
        if(!$basepath)
            $basepath = getenv('HOME'). "/.pheal/cache/";
        $this->basepath = $basepath;

        // Windows systems don't allow : as part of the filename
        $this->delimiter = (strtoupper (substr(PHP_OS, 0,3)) == 'WIN') ? "#" : ":";
    }

    /**
     * create a filename to use
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return string
     */
    protected function filename($userid, $apikey, $scope, $name, $args)
    {
        $argstr = "";

        foreach($args as $key => $val)
        {
            if(strlen($val) < 1)
                unset($args[$key]);
            elseif($key != 'userid' && $key != 'apikey')
                $argstr .= $key . $this->delimiter . $val . $this->delimiter;
        }
        $argstr = substr($argstr, 0, -1);
        $filename = "Request" . ($argstr ? "_" . $argstr : "") . ".xml";
        $filepath = $this->basepath . ($userid ? "$userid/$apikey/$scope/$name/" : "public/public/$scope/$name/");
        if(!file_exists($filepath))
            mkdir($filepath, 0777, true);
        return $filepath . $filename;
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
        $filename = $this->filename($userid, $apikey, $scope, $name, $args);
        if(!file_exists($filename))
            return false;
        $xml = join('', file($filename));
        if($this->validate_cache($xml, $name))
            return $xml;
        return false;

    }

    /**
     * validate the cached xml if it is still valid. This contains a name hack
     * to work arround EVE API giving wrong cachedUntil values
     * @param string $xml
     * @param string $name
     * @return boolean
     */
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

    /**
     * Save XML from cache
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @param string $xml
     */
    public function save($userid,$apikey,$scope,$name,$args,$xml) 
    {
        $filename= $this->filename($userid, $apikey, $scope, $name, $args);
        $fp = fopen($filename, "w");
        fwrite($fp, $xml);
        fclose($fp);
    }
}

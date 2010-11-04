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
 * Simple filearchive for the xml
 */
class PhealFileArchive implements PhealArchiveInterface
{
    /**
     * path where to store the xml
     * @var string
     */
    protected $basepath;

    /**
     * construct PhealFileCache,
     * @param string $basepath optional string on where to store files, defaults to the current/users/home/.pheal/cache/
     */
    public function __construct($basepath = false)
    {
        if(!$basepath)
            $basepath = $_ENV["HOME"]. "/.pheal/archive/";
        $this->basepath = $basepath;
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
            else
                $argstr .= "$key:$val:";
        }
        $argstr = substr($argstr, 0, -1);
        $filename = "Request_" . gmdate('Ymd-His') . ($argstr ? "_" . $argstr : "") . ".xml";
        $filepath = $this->basepath . gmdate("Ymd") . "/" . ($userid ? "private/$userid/$apikey/$scope/$name/" : "$scope/$name/");
        if(!file_exists($filepath))
            mkdir($filepath, 0777, true);
        return $filepath . $filename;
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

<?php
/*
 MIT License
 Copyright (c) 2010 - 2014 Daniel Hoffend, Peter Petermann

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

namespace Pheal\Cache;

/**
 * Simple filecache for the xml
 */

use Pheal\Exceptions\PhealException;

class FileStorage implements CanCache
{
    /**
     * Path where to store the xml
     *
     * @var string
     */
    protected $basepath;

    /**
     * Various options for the filecache
     * valid keys are: delimiter, umask, umask_directory
     *
     * @var array
     */
    protected $options = array(
        'delimiter' => ':',
        'umask' => 0666,
        'umask_directory' => 0777
    );

    /**
     * Initialise file storage cache.
     *
     * @param bool|string $basepath string on where to store files, defaults to the current/users/home/.pheal/cache/
     * @param array $options optional config array, valid keys are: delimiter, umask, umask_directory
     */
    public function __construct($basepath = false, $options = array())
    {
        if (!$basepath) {
            $this->basepath = getenv('HOME') . "/.pheal/cache/";
        } else {
            $this->basepath = (string) $basepath;
        }

        // Windows systems don't allow : as part of the filename
        $this->options['delimiter'] = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? "#" : ":";

        // add options
        if (is_array($options) && count($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * Create a filename to use
     *
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return string
     * @throws \Pheal\Exceptions\PhealException
     */
    protected function filename($userid, $apikey, $scope, $name, $args)
    {
        // secure input to make sure pheal don't write the files anywhere
        // user can define their own apikey/vcode
        // maybe this should be tweaked or hashed
        $regexp = "/[^a-z0-9,.-_=]/i";
        $userid = (int)$userid;
        $apikey = preg_replace($regexp, '_', $apikey);

        // build cache filename
        $argstr = "";
        foreach ($args as $key => $val) {
            if (strlen($val) < 1) {
                unset($args[$key]);
            } elseif (!in_array(strtolower($key), array('userid', 'apikey', 'keyid', 'vcode'))) {
                $argstr .= preg_replace($regexp, '_', $key) . $this->options['delimiter'] . preg_replace(
                    $regexp,
                    '_',
                    $val
                ) . $this->options['delimiter'];
            }
        }
        $argstr = substr($argstr, 0, -1);
        $filename = "Request" . ($argstr ? "_" . $argstr : "") . ".xml";
        $filepath = $this->basepath . ($userid ? "$userid/$apikey/$scope/$name/" : "public/public/$scope/$name/");

        if (!file_exists($filepath)) {
            // check write access
            if (!is_writable($this->basepath)) {
                throw new PhealException(sprintf("Cache directory '%s' isn't writeable", $filepath));
            }

            // create cache folder
            $oldUmask = umask(0);
            mkdir($filepath, $this->options['umask_directory'], true);
            umask($oldUmask);

        } else {
            // check write access
            if (!is_writable($filepath)) {
                throw new PhealException(sprintf("Cache directory '%s' isn't writeable", $filepath));
            }
            if (file_exists($filename) && !is_writeable($filename)) {
                throw new PhealException(sprintf("Cache file '%s' isn't writeable", $filename));
            }
        }
        return $filepath . $filename;
    }

    /**
     * Load XML from cache
     *
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return string|false
     */
    public function load($userid, $apikey, $scope, $name, $args)
    {
        $filename = $this->filename($userid, $apikey, $scope, $name, $args);
        if (!file_exists($filename)) {
            return false;
        }

        $xml = file_get_contents($filename);
        if ($this->validateCache($xml)) {
            return $xml;
        }

        return false;

    }

    /**
     * Validate the cached xml if it is still valid. This contains a name hack
     * to work arround EVE API giving wrong cachedUntil values
     *
     * @param string $xml
     * @return boolean
     */
    public function validateCache($xml)
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set("UTC");

        $xml = @new \SimpleXMLElement($xml);
        $dt = (int) strtotime($xml->cachedUntil);
        $time = time();

        date_default_timezone_set($tz);

        return (bool) ($dt > $time);
    }

    /**
     * Save XML from cache
     *
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @param string $xml
     * @return bool
     */
    public function save($userid, $apikey, $scope, $name, $args, $xml)
    {
        $filename = $this->filename($userid, $apikey, $scope, $name, $args);
        $exists = file_exists($filename);

        // save content
        $result = file_put_contents($filename, $xml);

        // chmod only new files
        if (!$exists) {
            chmod($filename, $this->options['umask']);
        }

        return (bool) $result;
    }
}

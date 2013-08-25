<?php
/*
 MIT License
 Copyright (c) 2010 - 2013 Daniel Hoffend, Peter Petermann

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
namespace Pheal\Log;

/**
 * null log, as a placeholder if no logging is used
 */

use Pheal\Core\Config;

class FileStorage implements CanLog
{
    /**
     * path where to store the logs
     * @var string
     */
    protected $basepath;
    /**
     * saved startTime to measure the response time
     * @var float
     */
    protected $startTime = 0;
    /**
     * save the response time after stop() or log()
     * @var float
     */
    protected $responseTime = 0;
    /**
     * various options for the filecache
     * valid keys are: access_log, error_log, access_format, error_format, trancate_apikey, umask, umask_directory
     * both logfiles are getting passed to strftime() in the case you want to crate a simple logrotate
     * @var array
     */
    protected $options = array(
        'access_log' => 'pheal_access.log',
        'error_log' => 'pheal_error.log',
        'access_format' => "%s [%s] %2.4fs %s\n",
        'error_format' => "%s [%s] %2.4fs %s \"%s\"\n",
        'truncate_apikey' => true,
        'umask' => 0666,
        'umask_directory' => 0777,
    );

    /**
     * construct
     * @param bool|string $basepath optional string on where to store files, defaults to ~/.pheal/cache/
     * @param array $options optional config array, valid keys are: delimiter, umask, umask_directory
     */
    public function __construct($basepath = false, $options = array())
    {
        if (!$basepath) {
            $basepath = getenv('HOME') . "/.pheal/log/";
        }
        $this->basepath = $basepath;

        // add options
        if (is_array($options) && count($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * Start of measure the response time
     */
    public function start()
    {
        $this->responseTime = 0;
        $this->startTime = $this->getmicrotime();
    }

    /**
     * returns current microtime
     * @return float
     */
    protected function getmicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * logs request api call including options
     * @param string $scope
     * @param string $name
     * @param array $opts
     * @return boolean
     */
    public function log($scope, $name, $opts)
    {
        // stop measure the response time
        $this->stop();

        // get filename, return if disabled
        if (!($filename = $this->filename('access_log'))) {
            return false;
        }

        // log
        file_put_contents(
            $filename,
            sprintf(
                $this->options['access_format'],
                date('r'),
                (Config::getInstance()->http_post ? 'POST' : 'GET'),
                $this->responseTime,
                $this->formatUrl($scope, $name, $opts)
            ),
            FILE_APPEND
        );
        return true;
    }

    /**
     * Stop of measure the response time
     */
    public function stop()
    {
        if (!$this->startTime) {
            return false;
        }

        // calc responseTime
        $this->responseTime = $this->getmicrotime() - $this->startTime;
        $this->startTime = 0;
    }

    /**
     * create a filename to use (type can be access_log or error_log)
     * @param string $type
     * @return string
     */
    protected function filename($type)
    {
        // get filename
        if (!($filename = $this->options[$type])) {
            return false;
        }

        // check for directory
        if (!file_exists($this->basepath)) {
            $oldUmask = umask(0);
            mkdir($this->basepath, $this->options['umask_directory'], true);
            umask($oldUmask);
        }

        // create full logfile name (incl. name passing through strftime
        $fullFilename = $this->basepath . strftime($filename);

        // create the logfile of not existing
        if (!file_exists($fullFilename)) {
            file_put_contents($fullFilename, '');
            chmod($fullFilename, $this->options['umask']);
        }
        return $fullFilename;
    }

    /**
     * returns formatted url for logging
     * @param string $scope
     * @param string $name
     * @param array $opts
     * @return string
     */
    protected function formatUrl($scope, $name, $opts)
    {
        // create url
        $url = Config::getInstance()->api_base . $scope . '/' . $name . ".xml.aspx";

        // truncacte apikey for log safety
        if ($this->options['truncate_apikey'] && count($opts)) {
            if (isset($opts['apikey'])) {
                $opts['apikey'] = substr($opts['apikey'], 0, 16) . "...";
            }
            if (isset($opts['vCode'])) {
                $opts['vCode'] = substr($opts['vCode'], 0, 16) . "...";
            }
        }

        // add post data
        if (Config::getInstance()->http_post) {
            $url .= " DATA: " . http_build_query($opts, '', '&');
        } elseif (count($opts)) { // add data to url
            $url .= '?' . http_build_query($opts, '', '&');
        }

        return $url;
    }

    /**
     * logs failed request api call including options and error message
     * @param string $scope
     * @param string $name
     * @param array $opts
     * @param string $message
     * @return boolean
     */
    public function errorLog($scope, $name, $opts, $message)
    {
        // stop measure the response time
        $this->stop();

        // get filename, return if disabled
        if (!($filename = $this->filename('error_log'))) {
            return false;
        }

        // remove htmltags, newlines, and coherent blanks
        $message = preg_replace("/(\s\s+|[\n\r])/", ' ', strip_tags($message));

        // log
        file_put_contents(
            $filename,
            sprintf(
                $this->options['error_format'],
                date('r'),
                (Config::getInstance()->http_post ? 'POST' : 'GET'),
                $this->responseTime,
                $this->formatUrl($scope, $name, $opts),
                $message
            ),
            FILE_APPEND
        );
        return true;
    }
}

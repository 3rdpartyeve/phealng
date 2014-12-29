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

namespace Pheal\Log;

use Pheal\Core\Config;

/**
 * Class FileStorage a simple file_put_contents based logger
 *
 * @package Pheal\Log
 * @deprecated
 */
class FileStorage implements CanLog
{
    // import response timer
    use ResponseTimerTrait;

    // import api url formatter
    use ApiUrlFormatterTrait;

    /**
     * path where to store the logs
     *
     * @var string
     */
    protected $basepath;

    /**
     * various options for the filecache
     * valid keys are: access_log, error_log, access_format, error_format, trancate_apikey, umask, umask_directory
     * both logfiles are getting passed to strftime() in the case you want to crate a simple logrotate
     *
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
     *
     * @param bool|string $basepath optional string on where to store files, defaults to ~/.pheal/cache/
     * @param array $options optional config array, valid keys are: delimiter, umask, umask_directory
     */
    public function __construct($basepath = false, $options = array())
    {
        if (!$basepath) {
            $this->basepath = getenv('HOME')."/.pheal/log/";
        } else {
            $this->basepath = (string)$basepath;
        }

        // add options
        if (is_array($options) && count($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }



    /**
     * logs request api call including options
     *
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
                $this->formatUrl($scope, $name, $opts, $this->options['truncate_apikey'])
            ),
            FILE_APPEND
        );

        return true;
    }


    /**
     * create a filename to use (type can be access_log or error_log)
     *
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
        $fullFilename = $this->basepath.strftime($filename);

        // create the logfile of not existing
        if (!file_exists($fullFilename)) {
            file_put_contents($fullFilename, '');
            chmod($fullFilename, $this->options['umask']);
        }

        return $fullFilename;
    }

    /**
     * logs failed request api call including options and error message
     *
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
                $this->formatUrl($scope, $name, $opts, $this->options['truncate_apikey']),
                $message
            ),
            FILE_APPEND
        );

        return true;
    }
}

<?php
/*
 MIT License
 Copyright (c) 2010 - 2015 Daniel Hoffend, Peter Petermann

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
 * Base class which is used for several memcache based storages
 */
abstract class MemcacheBase
{
    /**
     * Memcache options (connection)
     *
     * @var array
     */
    protected $options = array(
        'host' => 'localhost',
        'port' => 11211,
        'compressed' => 0,
        'prefix' => 'Pheal',
    );


    /**
     * Create a key (prepend options->prefix to not conflict with other keys)
     *
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return string
     */
    protected function getKey($userid, $apikey, $scope, $name, $args)
    {
        $key = implode('|', compact('userid', 'apikey', 'scope', 'name'));

        foreach ($args as $k => $v) {
            if (!in_array(strtolower($key), array('userid', 'apikey', 'keyid', 'vcode'))) {
                $key .= sprintf('|%s:%s', $k, $v);
            }
        }

        return sprintf('%s|%s', $this->options['prefix'], md5($key));
    }

    /**
     * Return the number of seconds the XML is valid. Will never be less than 1.
     *
     * @param string $xml
     * @return int
     */
    protected function getTimeout($xml)
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set("UTC");

        $xml = @new \SimpleXMLElement($xml);
        $dt = (int)strtotime($xml->cachedUntil);
        $time = time();

        date_default_timezone_set($tz);

        return max(1, $dt - $time);
    }
}

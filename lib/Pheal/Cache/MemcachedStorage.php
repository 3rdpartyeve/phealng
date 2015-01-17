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
 * Implements memcached into Pheal
 */
class MemcachedStorage extends MemcacheBase implements CanCache
{
    /**
     * Active memcached instance/connection
     *
     * @var \Memcached
     */
    protected $memcached;
    protected $flags;

    /**
     * Initialise memcached storage cache.
     *
     * @param array $options optional config array, valid keys are: host, port
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->options;
        $this->memcached = new \Memcached();
        $this->memcached->addServer($this->options['host'], $this->options['port'], 0);
        $this->flags = ($this->options['compressed']) ? MEMCACHED_COMPRESSED : 0;
    }

    /**
     * Load XML from cache
     *
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return string
     */
    public function load($userid, $apikey, $scope, $name, $args)
    {
        $key = $this->getKey($userid, $apikey, $scope, $name, $args);

        $age_data = $this->memcached->get($key.'_age');
        $age_result = $this->memcached->getResultCode();

        if ($age_result != 0) {
            return false;
        }

        $age = time() - (int)$age_data['age'];

        if ($age > (int)$age_data['ttl']) {
            return false;
        }

        $read = (string)$this->memcached->get($key);
        $read_result = $this->memcached->getResultCode();

        if ($read_result != 0) {
            return false;
        }

        return $read;
    }

    /**
     * Save XML to cache
     *
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @param string $xml
     * @return bool|void
     */
    public function save($userid, $apikey, $scope, $name, $args, $xml)
    {
        $key = $this->getKey($userid, $apikey, $scope, $name, $args);
        $ttl = $this->getTimeout($xml);
        $time = time();
        $timeout = $time + $ttl;

        $replace = $this->memcached->replace($key, $xml, $timeout);

        $set = $set_age = false;

        if (!$replace) {
            $set = $this->memcached->set($key, $xml, $timeout);
        }

        $replace_age = $this->memcached->replace($key.'_age', array('age' => $time, 'ttl' => $ttl), $timeout);

        if (!$replace_age) {
            $set_age = $this->memcached->set($key.'_age', array('age' => $time, 'ttl' => $ttl), $timeout);
        }

        return (($replace || $set) && ($replace_age || $set_age));
    }
}

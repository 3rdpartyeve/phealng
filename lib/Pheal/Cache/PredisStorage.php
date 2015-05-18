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

use Pheal\Exceptions\PhealException;

/**
 * Redis cache storage for Pheal. Using Predis
 * "predis/predis": "~1.0"
 */
class PredisStorage implements CanCache
{

    /**
     * Redis connection interface
     *
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * Redis connection options
     * `host` can be either IP/hostname(127.0.0.1) or UNIX socket(/tmp/path/to/redis.sock)
     *
     * @var array
     */
    protected $options = array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'persistent' => true,
        'password' => null,
        'prefix' => 'Pheal'
    );

    /**
     * Load XML from cache
     *
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return false|string
     */
    public function load($userid, $apikey, $scope, $name, $args)
    {
        $key = $this->getKey($userid, $apikey, $scope, $name, $args);

        return $this->redis->get($key);
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
     * @return boolean
     */
    public function save($userid, $apikey, $scope, $name, $args, $xml)
    {
        $key = $this->getKey($userid, $apikey, $scope, $name, $args);
        $timeout = $this->getTimeout($xml);

        return $this->redis->set($key, $xml, $timeout);
    }

    /**
     * Create a redis key (prepend `prefix` to not conflict with other keys)
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

        return sprintf('%s|%s', $this->options['prefix'], $key);
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
        date_default_timezone_set('UTC');

        $xml = @new \SimpleXMLElement($xml);
        $dt = (int) strtotime($xml->cachedUntil);
        $time = time();

        date_default_timezone_set($tz);

        return max(1, $dt - $time);
    }

    /**
     * Initialise redis storage cache. Save configuration options
     *
     * @param array $options
     * @throws \Pheal\Exceptions\PhealException
     * @return \Pheal\Cache\RedisStorage
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->options;
        $this->redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host'   => $this->options['host'],
            'port'   => $this->options['port'],
            'password' => $this->options['password'],
        ]);
    }
}

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

class Adaptable
{

    /**
     * Adaptable methods
     *
     * @var array
     */
    protected $options = array(
        'load' => false,
        'save' => false
    );

    /**
     * Construct a cache adapter
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options + $this->options;
    }

    /**
     * Load a cache entry via the adapter
     *
     * @param string $key
     * @return string|false
     */
    public function load($key)
    {
        $callable = $this->options['load'];

        if (!is_callable($callable)) {
            return false;
        }

        return call_user_func_array($callable, array($key));
    }

    /**
     * Save a cache entry via the adapter
     *
     * @param string $key
     * @param string $data
     * @param integer $timeout
     * @return boolean
     */
    public function save($key, $data, $timeout)
    {
        $callable = $this->options['save'];

        if (!is_callable($callable)) {
            return false;
        }

        return call_user_func_array($callable, array($key, $data, $timeout));
    }
}

<?php
/*
 MIT License
 Copyright (c) 2010 - 2013 Peter Petermann

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
namespace Pheal\Core;

/**
 * Pheal Result Object
 */

use Pheal\Exceptions\APIException;

class Result implements CanConvertToArray
{
    /**
     * time at which the API got the request
     * @var string
     */
    public $request_time;

    /**
     * time at which the API got the request as unixtimestamp
     * @var int
     */
    public $request_time_unixtime;

    /**
     * time till the cache should hold this result
     * @var string
     */
    public $cached_until;

    /**
     * time till the cache should hold this result
     * @var int
     */
    public $cached_until_unixtime;

    /**
     * root element of the result
     * @var \Pheal\Core\Element|\Pheal\Core\RowSet
     */
    private $rootElement = null;

    /**
     * initializes the PhealResult
     *
     * @param \SimpleXMLElement $xml
     * @throws \Pheal\Exceptions\APIException
     */
    public function __construct($xml)
    {
        // switch to UTC
        $oldtz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $this->request_time = (string)$xml->currentTime;
        $this->cached_until = (string)$xml->cachedUntil;
        $this->request_time_unixtime = (int)strtotime($xml->currentTime);
        $this->cached_until_unixtime = (int)strtotime($xml->cachedUntil);

        // workaround if cachedUntil is missing in API response (request + 1 hour)
        if (!$this->cached_until) {
            $this->cached_until_unixtime = $this->request_time_unixtime + 60 * 60;
            $this->cached_until = date('Y-m-d H:i:s', $this->cached_until_unixtime);
        }

        // switch back to normal time
        date_default_timezone_set($oldtz);

        // error detection
        if ($xml->error) {
            throw new APIException((int)$xml->error["code"], (String)$xml->error, $xml);
        }
        $this->rootElement = Element::parseElement($xml->result);
    }

    /**
     * magic method, forwarding attribute access to $this->element
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->rootElement->$name;
    }

    /**
     * magic method, allow for isset/empty
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->rootElement->$name);
    }

    /**
     * returns the Object as associated array
     * @return array
     */
    public function toArray()
    {
        if ($this->rootElement instanceof CanConvertToArray) {
            return array(
                'currentTime' => $this->request_time,
                'cachedUntil' => $this->cached_until,
                'result' => $this->rootElement->toArray()
            );
        } else {
            return array();
        }
    }
}

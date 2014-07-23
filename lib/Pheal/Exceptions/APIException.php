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

namespace Pheal\Exceptions;

/**
 * Exception to be thrown if the EVE API returns an error
 */
class APIException extends PhealException
{
    /**
     * EVE API Errorcode
     * @var int
     */
    public $code;

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
     * construct exception with EVE API errorcode, and message
     * @param int $code
     * @param string $message
     * @param \SimpleXMLElement $xml
     */
    public function __construct($code, $message, $xml)
    {
        $this->code = (int)$code;

        // switch to UTC
        $oldtz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        // save request/cache timers (if ccp provides them)
        if ($xml->currentTime) {
            $this->request_time = (string)$xml->currentTime;
            $this->request_time_unixtime = (int)strtotime($xml->currentTime);
        }
        if ($xml->cachedUntil) {
            $this->cached_until = (string)$xml->cachedUntil;
            $this->cached_until_unixtime = (int)strtotime($xml->cachedUntil);
        }

        // switch back to normal time
        date_default_timezone_set($oldtz);

        parent::__construct($message);
    }
}

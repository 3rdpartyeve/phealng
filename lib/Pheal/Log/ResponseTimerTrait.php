<?php
/*
 MIT License
 Copyright (c) 2010 - 2015 Peter Petermann

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
 * ResponseTimerTrait
 * a trait which provides an way to measure response times for loggers
 *
 * @package Pheal\Log
 */
trait ResponseTimerTrait
{
    /**
     * saved startTime to measure the response time
     *
     * @var float
     */
    protected $startTime = 0.0;

    /**
     * save the response time after stop() or log()
     *
     * @var float
     */
    protected $responseTime = 0.0;

    /**
     * Start of measure the response time
     */
    public function start()
    {
        $this->responseTime = 0.0;
        $this->startTime = $this->getmicrotime();
    }

    /**
     * returns current microtime
     *
     * @return float
     */
    protected function getmicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());

        return ((float)$usec + (float)$sec);
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
        $this->startTime = 0.0;
    }
}

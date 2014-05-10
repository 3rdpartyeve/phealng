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

/**
 * Interface that should be implemented by the archive handlers
 */
interface CanLog
{
    /**
     * Start of measure the response time
     * @return boolean
     */
    public function start();

    /**
     * Stop of measure the response time
     * @return boolean
     */
    public function stop();

    /**
     * logs request api call including options
     * @param string $scope
     * @param string $name
     * @param array $opts
     * @return boolean
     */
    public function log($scope, $name, $opts);

    /**
     * logs failed request api call including options and error message
     * @param string $scope
     * @param string $name
     * @param array $opts
     * @param string $message
     * @return boolean
     */
    public function errorLog($scope, $name, $opts, $message);
}

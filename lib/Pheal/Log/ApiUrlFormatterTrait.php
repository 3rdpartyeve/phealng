<?php
/*
 MIT License
 Copyright (c) 2010 - 2015 Peter Petermann, Daniel Hoffend

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
 * ApiUrlFormatterTrait provide a simple formatter for logging api requests
 *
 * @package Pheal\Log
 */
trait ApiUrlFormatterTrait
{
    /**
     * returns formatted url for logging
     *
     * @param string $scope
     * @param string $name
     * @param array $opts
     * @param bool $truncateKey
     * @return string
     */
    protected function formatUrl($scope, $name, $opts, $truncateKey = true)
    {
        // create url
        $url = Config::getInstance()->api_base.$scope.'/'.$name.".xml.aspx";

        // truncacte apikey for log safety
        if ($truncateKey && count($opts)) {
            if (isset($opts['apikey'])) {
                $opts['apikey'] = substr($opts['apikey'], 0, 16)."...";
            }
            if (isset($opts['vCode'])) {
                $opts['vCode'] = substr($opts['vCode'], 0, 16)."...";
            }
        }

        // add post data
        if (Config::getInstance()->http_post) {
            $url .= " DATA: ".http_build_query($opts, '', '&');
        } elseif (count($opts)) { // add data to url
            $url .= '?'.http_build_query($opts, '', '&');
        }

        return $url;
    }
}

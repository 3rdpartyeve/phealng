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
namespace Pheal\Fetcher;

/**
 * filefetcher, which uses file_get_contents to fetch the api data
 * remember: on some installations, file_get_contents(url) might not be available due to
 * restrictions via allow_url_fopen
 * this handler is unable to process error responses by ccp which have an http error
 * status code, since file_get_contents retursn false in that case
 */

use Pheal\Core\Config;
use Pheal\Exceptions\ConnectionException;
use Pheal\Pheal;

class File implements CanFetch
{
    /**
     * method will do the actual http call using file()
     * @param String $url url beeing requested
     * @param array $opts an array of query paramters
     * @throws \Pheal\Exceptions\PhealException
     * @throws \Pheal\Exceptions\HTTPException
     * @return string raw http response
     */
    public function fetch($url, $opts)
    {
        // initialize this php abomination
        $php_errormsg = null;

        $options = array();

        $options['http'] = array();
        $options['http']['ignore_errors'] = true;

        // set custom user agent
        $options['http']['user_agent'] = 'PhealNG/' . Pheal::VERSION . ' ' . Config::getInstance()->http_user_agent;


        // set custom http timeout
        if (($http_timeout = Config::getInstance()->http_timeout) != false) {
            $options['http']['timeout'] = $http_timeout;
        }

        // ignore ssl peer verification if needed
        if (substr($url, 0, 5) == "https") {
            $options['ssl']['verify_peer'] = Config::getInstance()->http_ssl_verifypeer;
        }

        // use post for params
        if (count($opts) && Config::getInstance()->http_post) {
            $options['http']['method'] = 'POST';
            $options['http']['content'] = http_build_query($opts, '', '&');
        } elseif (count($opts)) { // else build url parameters
            $url .= "?" . http_build_query($opts, '', '&');
        }

        // set track errors. needed for $php_errormsg
        $oldTrackErrors = ini_get('track_errors');
        ini_set('track_errors', true);

        // create context with options and request api call
        // suppress the 'warning' message which we'll catch later with $php_errormsg
        if (count($options)) {
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
        } else {
            $result = @file_get_contents($url);
        }

        // check for http errors via magic $http_response_header
        $httpCode = 200;
        if (isset($http_response_header[0])) {
            list($httpVersion, $httpCode, $httpMsg) = explode(' ', $http_response_header[0], 3);
        }
        // http errors
        if (is_numeric($httpCode) && $httpCode >= 400) {
            // ccp is using error codes even if they send a valid application
            // error response now, so we have to use the content as result
            // for some of the errors. This will actually break if CCP ever uses
            // the HTTP Status for an actual transport related error.
            switch($httpCode) {
                case 400:
                case 403:
                case 500:
                case 503:
                    return $result;
                    break;
                default:
            }
            throw new \Pheal\Exceptions\HTTPException($httpCode, $url);
        }


        // throw error
        if ($result === false) {
            $message = ($php_errormsg ? $php_errormsg : 'HTTP Request Failed');

            // set track_errors back to the old value
            ini_set('track_errors', $oldTrackErrors);

            throw new ConnectionException($message);

            // return result
        } else {
            // set track_errors back to the old value
            ini_set('track_errors', $oldTrackErrors);
            return $result;
        }

    }
}

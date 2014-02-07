<?php

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
        $options = array();

        $options['http'] = array();
        $options['http']['ignore_errors'] = true;

        // set custom user agent
        if (($http_user_agent = Config::getInstance()->http_user_agent) != false) {
            $options['http']['user_agent'] = $http_user_agent;
        }

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

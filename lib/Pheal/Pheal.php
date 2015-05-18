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

namespace Pheal;

/**
 * Pheal (PHp Eve Api Library), a EAAL Port for PHP
 */

use Pheal\Core\Config;
use Pheal\Core\Result;
use Pheal\Exceptions\ConnectionException;
use Pheal\Exceptions\HTTPException;
use Pheal\Exceptions\PhealException;

/**
 * Class Pheal
 * @method Result APIKeyInfo()
 */
class Pheal
{
    /**
     * Version container
     *
     * @var string
     */
    const VERSION = "2.2.0";

    /**
     * @var int
     */
    private $userid;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string|null
     */
    private $keyType;

    /**
     * @var int
     */
    private $accessMask;

    /**
     * EVE Api scope to be used (for example: "account", "char","corp"...)
     * @var string
     */
    public $scope;

    /**
     * Result of the last XML request, so application can use the raw xml data
     * @var String
     */
    public $xml;

    /**
     * creates new Pheal API object
     * @param int $userid the EVE userid/keyID
     * @param string $key the EVE apikey/vCode
     * @param string $scope to use, defaults to account. can be changed during runtime by modifying attribute "scope"
     */
    public function __construct($userid = null, $key = null, $scope = "account")
    {
        $this->userid = $userid;
        $this->key = $key;
        $this->scope = $scope;
    }

    /**
     * Magic __call method, will translate all function calls to object to API requests
     * @param String $name name of the function
     * @param array $arguments an array of arguments
     * @return \Pheal\Core\Result
     */
    public function __call($name, $arguments)
    {
        if (count($arguments) < 1 || !is_array($arguments[0])) {
            $arguments[0] = array();
        }
        $scope = $this->scope;
        return $this->requestXml($scope, $name, $arguments[0]); // we only use the
        //first argument params need to be passed as an array, due to naming

    }

    /**
     * Magic __get method used to set scope
     * @param string $name name of the scope e.g. "mapScope"
     * @return \Pheal\Pheal|null
     */
    public function __get($name)
    {
        if (preg_match('/(.+)Scope$/', $name, $matches) == 1) {
            $this->scope = $matches[1];
            return $this;
        }
        return null;
    }

    /**
     * Set keyType/accessMask
     * @param string $keyType   must be Account/Character/Corporation or null
     * @param int $accessMask   must be integer or 0
     * @return void
     */
    public function setAccess($keyType = null, $accessMask = 0)
    {
        $this->keyType = in_array(
            ucfirst(strtolower($keyType)),
            array('Account', 'Character', 'Corporation')
        ) ? $keyType : null;
        $this->accessMask = (int)$accessMask;
    }

    /**
     * clear+reset keyType/accessMask
     * @return void
     */
    public function clearAccess()
    {
        $this->setAccess();
    }

    /**
     * if userid+key is given it automatically detects (api call) the keyinfo and
     * set the correct access level for futher checks.
     *
     * Keep in mind this method will make an api request to account/APIKeyInfo based
     * on the given \Pheal\Core\Config settings with the given key credentials.
     *
     * More important! This method will throw Exceptions on invalid keys or networks errors
     * So place this call into your try statement
     *
     * @throws \Pheal\Exceptions\PhealException|\Pheal\Exceptions\APIException|\Pheal\Exceptions\HTTPException
     * @return boolean|\Pheal\Core\Result
     */
    public function detectAccess()
    {
        // don't request keyinfo if api keys are not set or if new CAK aren't enabled
        if (!$this->userid || !$this->key || !Config::getInstance()->api_customkeys) {
            return false;
        }

        // request api key info, save old scope and restore it afterwords
        $old = $this->scope;
        $this->scope = "account";
        $keyinfo = $this->APIKeyInfo();
        $this->scope = $old;

        // set detected keytype and accessMask
        $this->setAccess($keyinfo->key->type, $keyinfo->key->accessMask);

        // return the APIKeyInfo Result object in the case you need it.
        return $keyinfo;
    }

    /**
     * method will ask caching class for valid xml, if non valid available
     * will make API call, and return the appropriate result
     *
     * @param string $scope api scope (examples: eve, map, server, ...)
     * @param string $name api method (examples: ServerStatus, Kills, Sovereignty, ...)
     * @param array $opts additional args (example.: characterID => 12345), shouldn't contain apikey/userid/keyid/vcode
     *
     * @throws \Pheal\Exceptions\ConnectionException
     * @throws \Pheal\Exceptions\PhealException
     * @throws \Pheal\Exceptions\HTTPException
     * @throws \Exception
     * @return \Pheal\Core\Result
     */
    private function requestXml($scope, $name, array $opts = array())
    {
        $opts = array_merge(Config::getInstance()->additional_request_parameters, $opts);

        // apikey/userid/keyid|vcode shouldn't be allowed in arguments and removed to avoid wrong cached api calls
        foreach ($opts as $k => $v) {
            if (in_array(strtolower($k), array('userid', 'apikey', 'keyid', 'vcode'))) {
                unset($opts[$k]);
            }
        }

        // prepare http arguments + url (to not modify original argument list for cache saving)
        $url = Config::getInstance()->api_base . $scope . '/' . $name . ".xml.aspx";
        $use_customkey = (bool)Config::getInstance()->api_customkeys;
        $http_opts = $opts;
        if ($this->userid) {
            $http_opts[($use_customkey ? 'keyID' : 'userid')] = $this->userid;
        }
        if ($this->key) {
            $http_opts[($use_customkey ? 'vCode' : 'apikey')] = $this->key;
        }

        // check access level if given (throws PhealAccessExpception if API call is not allowed)
        if ($use_customkey && $this->userid && $this->key && $this->keyType) {
            try {
                Config::getInstance()->access->check($scope, $name, $this->keyType, $this->accessMask);
            } catch (\Exception $e) {
                Config::getInstance()->log->errorLog($scope, $name, $http_opts, $e->getMessage());
                throw $e;
            }
        }

        // check cache first
        if (!$this->xml = Config::getInstance()->cache->load($this->userid, $this->key, $scope, $name, $opts)) {
            try {
                // start measure the response time
                Config::getInstance()->log->start();

                // rate limit
                Config::getInstance()->rateLimiter->rateLimit();

                // request
                $this->xml = Config::getInstance()->fetcher->fetch($url, $http_opts);

                // stop measure the response time
                Config::getInstance()->log->stop();


                $element = @new \SimpleXMLElement($this->xml);

                // check if we could parse this
                if ($element === false) {
                    $errmsgs = "";
                    foreach (libxml_get_errors() as $error) {
                        $errmsgs .= $error->message . "\n";
                    }
                    throw new PhealException('XML Parser Error: ' . $errmsgs);
                }

                // archive+save only non-error api calls + logging
                if (!$element->error) {
                    Config::getInstance()->log->log($scope, $name, $http_opts);
                    Config::getInstance()->archive->save($this->userid, $this->key, $scope, $name, $opts, $this->xml);
                } else {
                    Config::getInstance()->log->errorLog(
                        $scope,
                        $name,
                        $http_opts,
                        $element->error['code'] . ': ' . $element->error
                    );
                }

                Config::getInstance()->cache->save($this->userid, $this->key, $scope, $name, $opts, $this->xml);
                // just forward HTTP Errors
            } catch (HTTPException $e) {
                throw $e;
                // ensure that connection exceptions are passed on
            } catch (ConnectionException $e) {
                throw $e;
                // other request errors
            } catch (\Exception $e) {
                // log + throw error
                Config::getInstance()->log->errorLog(
                    $scope,
                    $name,
                    $http_opts,
                    $e->getCode() . ': ' . $e->getMessage()
                );
                throw new PhealException('Original exception: ' . $e->getMessage(), $e->getCode(), $e);
            }

        } else {
            $element = @new \SimpleXMLElement($this->xml);
        }
        return new Result($element);
    }
}

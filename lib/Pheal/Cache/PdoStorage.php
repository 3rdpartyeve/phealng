<?php
/*
 MIT License
 Copyright (c) 2014 Matthias Kühne, Peter Petermann

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
 * Database caching via PDO
 *
 * @see the README.md for the create table statement
 */
class PdoStorage implements CanCache
{
    /**
     * The PDO database object
     *
     * @var \Pdo
     */
    protected $db;

    /**
     * Various options for the database cache
     * valid keys are: table
     *
     * @var array
     */
    protected $options = array(
        'table' => 'phealng-cache',
    );

    /**
     * The prepared statements for the Pdo-Database
     *
     * @var type
     */
    protected $statements = array();

    /**
     * Constructs the database cache
     * Be aware that the table must exist before you can cache!
     *
     * @param string $dsn the DSN needed for PDO to connect to the database
     * @param string $username the username for the database connection
     * @param string $password the password for the database connection
     * @param bool|false|string $table the table name for the cache (defaults to "phealng-cache")
     * @param array $dbOptions (optional) additional options for PDO
     * @throws PhealException
     */
    public function __construct($dsn, $username, $password, $table = false, array $dbOptions = array())
    {
        $this->db = new \PDO($dsn, $username, $password, $dbOptions);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        if ($table !== false) {
            if (strpos($table, '`') !== false) {
                throw new PhealException('The table name mustn\'t have backticks!');
            }
            $this->options['table'] = $table;
        }
    }

    /**
     * Prepares the PDO statements
     */
    protected function prepareStatements()
    {
        if (!empty($this->statements)) {
            return;
        }
        $this->statements['load'] = $this->db->prepare(
            'SELECT * FROM `' . $this->options['table'] . '` WHERE `userId` = :userId '
            . 'AND `scope` = :scope AND `name` = :name AND `args` = :args'
        );

        $this->statements['save'] = $this->db->prepare(
            'INSERT INTO `' . $this->options['table'] . '` (`userId`, `scope`, `name`, `args`, `cachedUntil`, `xml`) VALUES '
            . '(:userId, :scope, :name, :args, :cachedUntil, :xml)'
        );

        $this->statements['delete'] = $this->db->prepare(
            'DELETE FROM`' . $this->options['table'] . '` WHERE `userId` = :userId '
            . 'AND `scope` = :scope AND `name` = :name AND `args` = :args'
        );
    }

    /**
     * Serializes the arguments into a string
     *
     * @param array $args
     * @return string the serialized arguments array, e. g. ;key1=value1;key2=value2
     */
    protected function serializeArguments(array $args)
    {
        // first we have to sort the args by key to avoid that different orders lead to different cache entries!
        ksort($args);

        $result = '';
        $invalidKeys = array('userid', 'apikey', 'keyid', 'vcode');
        foreach ($args as $key => $value) {
            $key   = trim($key);
            $value = trim($value);
            if (empty($value) || in_array($key, $invalidKeys)) {
                // ignore the invalid keys...
                continue;
            }
            $result .= ';' . $key . '=' . $value;
        }

        return $result;
    }

    /**
     * Load XML from cache
     *
     * @param int $userid
     * @param string $apikey (unused!)
     * @param string $scope
     * @param string $name
     * @param array $args
     * @return false|string
     * @throws PhealException if something happened during the Database operation
     */
    public function load($userid, $apikey, $scope, $name, $args)
    {
        $this->prepareStatements();

        $argumentString = $this->serializeArguments($args);

        $statement = $this->statements['load'];
        /* @var $statement \PDOStatement */

        if ($userid === null) {
            // Fix for null value userids
            // e. g. for global calls to /eve/
            $userid = 0;
        }

        try {
            $statement->execute(
                array(
                    ':userId' => $userid,
                    ':scope'  => $scope,
                    ':name'   => $name,
                    ':args'   => $argumentString,
                )
            );

            $result = $statement->fetch();
        } catch (\PDOException $e) {
            throw new PhealException('Loading from cache failed!', null, $e);
        }

        if (empty($result)) {
            return false;
        }

        if (!$this->validateCache($result['cachedUntil'])) {
            return false;
        }

        return $result['xml'];
    }

    /**
     * Validate the cached xml if it is still valid. This contains a name hack
     * to work arround EVE API giving wrong cachedUntil values
     *
     * @param int $cachedUntil
     * @return boolean
     */
    public function validateCache($cachedUntil)
    {
        $tz = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $dt   = (int) strtotime($cachedUntil);
        $time = time();

        date_default_timezone_set($tz);

        return (bool) ($dt > $time);
    }

    /**
     * Save XML to cache
     *
     * @param int $userid
     * @param string $apikey
     * @param string $scope
     * @param string $name
     * @param array $args
     * @param string $xml
     * @return bool|void
     * @throws PhealException if something happened during the Database operations
     */
    public function save($userid, $apikey, $scope, $name, $args, $xml)
    {
        $this->prepareStatements();

        $argumentString = $this->serializeArguments($args);

        /* @var $deleteStatement \PDOStatement */
        $deleteStatement = $this->statements['delete'];

        if ($userid === null) {
            // Fix for null value userids
            // e. g. for global calls to /eve/
            $userid = 0;
        }

        try {
            $deleteStatement->execute(
                array(
                    ':userId' => $userid,
                    ':scope'  => $scope,
                    ':name'   => $name,
                    ':args'   => $argumentString,
                )
            );
        } catch (\PDOException $e) {
            throw new PhealException('Deleting old cache entries failed!', null, $e);
        }

        /* @var $statement \PDOStatement */
        $statement = $this->statements['save'];

        try {
            $xml = new \SimpleXMLElement($xml);

            $statement->execute(
                array(
                    ':userId' => $userid,
                    ':scope'  => $scope,
                    ':name'   => $name,
                    ':args'   => $argumentString,
                    ':cachedUntil' => $xml->cachedUntil,
                    ':xml'    => $xml->asXML(),
                )
            );
        } catch (\PDOException $e) {
            throw new PhealException('Saving to cache failed!', null, $e);
        }
    }
}

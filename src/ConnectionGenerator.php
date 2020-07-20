<?php
/**
 * Config: setup the database configuration for PDO.
 */

namespace SypherLev\Blueprint;

use SypherLev\Blueprint\Error\BlueprintException;

class ConnectionGenerator
{
    private $driver = '';
    private $host = '';
    private $database = '';
    private $user = '';
    private $pass = '';

    /**
     * Setup the connection parameters for PDO
     *
     * @param string $driver
     * @param string $host
     * @param string $database
     * @param string $user
     * @param string $pass
     */
    public function setConnectionParameters(
        string $host,
        string $database,
        string $user,
        string $pass,
        string $driver = 'mysql'
    )
    {
        $this->driver = $driver;
        $this->host = $host;
        $this->database = $database;
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * Generate a new PDO instance using validated parameters, or throw an Exception
     *
     * @return \PDO
     * @throws \Exception
     */
    public function generateNewPDO() :\PDO
    {
        if($this->validateConfig()) {
            $dns = $this->driver . ':dbname=' . $this->database . ";host=" . $this->host;
            return new \PDO($dns, $this->user, $this->pass);
        }
        else {
            throw (new BlueprintException("Invalid or missing database connection parameters"));
        }
    }

    private function validateConfig() : bool {
        if($this->driver == '' ||
            $this->host == '' ||
            $this->database == '' ||
            $this->user == '' ||
            $this->pass == ''
        ) {
            return false;
        }
        return true;
    }
}
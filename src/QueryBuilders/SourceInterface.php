<?php

namespace SypherLev\Blueprint\QueryBuilders;

interface SourceInterface
{
    // TERMINATION METHODS
    // these methods are used to end the query chain, clear the query, and return a result

    /**
     * Execute the current query and return a single result.
     * Arbitrary SQL and binds can be passed in for debugging purposes.
     * Returns the result as an object or boolean if nothing found/error occured.
     *
     * @param bool $sql
     * @param bool $binds
     * @return object|boolean
     */
    public function one($sql = false, $binds = false);

    /**
     * Execute the current query and return an array of results.
     * Arbitrary SQL and binds can be passed in for debugging purposes
     *
     * @param bool $sql
     * @param bool $binds
     * @return array
     */
    public function many($sql = false, $binds = false);

    /**
     * Execute the current query and return a single integer count.
     * Returns integer count or boolean if error occurred.
     *
     * @return integer|boolean
     */
    public function count();

    /**
     * Execute the current query and return true or false.
     * Arbitrary SQL and binds can be passed in for debugging purposes
     *
     * @param bool $sql
     * @param bool $binds
     * @return boolean
     */
    public function execute($sql = false, $binds = false);

    /**
     * WARNING: Don't use this unless you know what you're doing
     *
     * Executes a raw prepared statement on the current database connection without
     * using the compiler. Returns an error, a boolean for success/fail, or an array of results
     *
     * TO DO: make the return values less stupid
     *
     * @param $sql - a prepared SQL statement
     * @param $values - an array of corresponding bind values: array(':vm1' => $value)
     * @param string $fetch - (optional) set as 'fetch' or 'fetchAll' to get results
     * @param int $returntype - defaults to PDO::FETCH_OBJ, must be a PDO return type
     * @return array|bool|\Exception|mixed
     */
    public function raw($sql, $values, $fetch = '', $returntype = \PDO::FETCH_OBJ);

    /**
     * Reset the current query
     */
    public function reset();

    // UTILITY METHODS

    /**
     * Alias for PDO::lastInsertId
     *
     * @param null $name
     * @return int
     */
    public function lastInsertId($name = "");

    /**
     * Alias for PDO::beginTransaction with some additional tracking
     */
    public function beginTransaction();

    /**
     * Alias for PDO::commit with some additional tracking
     */
    public function commit();

    /**
     * Alias for PDO::rollBack with some additional tracking
     */
    public function rollBack();

    // TESTING METHODS
    // these methods are used to check outputs and do query testing

    /**
     * Starts the query recorder
     */
    public function startRecording();

    /**
     * Stops the query recorder
     */
    public function stopRecording();

    /**
     * Gets an array of recorded queries consisting of the generated SQL, bindings, and PDO error output
     *
     * @return array
     */
    public function getRecordedOutput();

    /**
     * Get a list of columns from a table in the current database
     *
     * @param $tableName
     * @return array
     */
    public function getTableColumns($tableName);

    /**
     * Returns the primary key of a table in the current database
     *
     * @param $tableName
     * @return string
     */
    public function getPrimaryKey($tableName);

    /**
     * Sets the current query object
     *
     * @param $query
     */
    public function setQuery(QueryInterface $query);

    /**
     * Generate a new QueryInterface object appropriate for this source's driver
     */
    public function generateNewQuery();
}
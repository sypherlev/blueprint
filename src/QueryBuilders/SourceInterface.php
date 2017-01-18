<?php

namespace SypherLev\Blueprint\QueryBuilders;

interface SourceInterface
{
    public function one($sql = false, $binds = false);

    public function many($sql = false, $binds = false);

    public function count();

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

    public function reset();

    public function getSchemaName();

    public function getTableColumns($tableName);

    public function lastInsertId($name = null);

    public function beginTransaction();

    public function commit();

    public function rollBack();

    public function startRecording();

    public function stopRecording();

    public function getRecordedOutput();

    /**
     * Copies and returns the current query - useful for storing/rerunning failed queries
     *
     * @return $query
     */
    public function cloneQuery();

    /**
     * Sets the current query to a cloned copy from $this->cloneQuery
     *
     * @param $query
     */
    public function setQuery(QueryInterface $query);
}
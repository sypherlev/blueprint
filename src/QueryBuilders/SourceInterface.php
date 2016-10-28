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
     * Executes a raw prepared SQL statement on the current database connection without
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

    /**
     * Gets a plain PHP object which contains the query and bindings. This is useful if you
     * want to execute the query elsewhere.
     *
     * @return \stdClass
     */
    public function retrieveQuery();

    /**
     * Sets a whitelist for the columns in the case where the query is executing with
     * user input as the column names. Takes an array or a string
     *
     * @param array|string $whitelist
     * @return $this
     */
    public function addToColumnWhitelist($whitelist);

    /**
     * Sets a whitelist for the tables in the case where the query is executing with
     * user input as the table names. Applies to the primary table and all joined tables
     * Accepts an array or a string
     *
     * @param array|string $whitelist
     * @return $this
     */
    public function addToTableWhitelist($whitelist);

    /**
     * Shorthand method to get the last saved ID from a table. Allows for ID names other than 'id'
     *
     * @param $table
     * @param string $primaryKeyname
     * @return mixed
     */
    public function lastIdFrom($table, $primaryKeyname = 'id');

    public function lastInsertId($name = null);

    public function startTransaction();

    public function commitTransaction();

    public function rollbackTransaction();

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

    /**
     * Returns the current raw SQL statement based on the parameters in $this->currentquery
     * or throws an \Exception if required elements are missing
     *
     * @return mixed
     */
    public function getCurrentSQL();

    public function select();

    public function update();

    public function insert();

    public function delete();

    /**
     * Selects columns to attach to the query
     *
     * @param $columnName_or_columnArray - has five possible types:
     *     $column
     *     array($columnone, $columntwo, ...)
     *     array($alias => $column, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     *     array($tableone => array($aliasone => $columnone, $aliastwo => $columntwo,  ...), $tabletwo => array(...) ...)
     * @return $this
     */
    public function columns($columnname_or_columnarray);

    /**
     * Set the primary table on the query
     *
     * @param string $tablename
     * @return $this
     */
    public function table($tablename);

    /**
     * Used to add records for INSERT statements
     * Use this in a loop to add a batch of records
     *
     * @param array $record - array('column' => $variable, ... )
     * @return $this
     */
    public function add(Array $record);

    /**
     * Used only with UPDATE
     *
     * @param array $set - array('column' => $variable, ... )
     * @return $this
     */
    public function set(Array $set);

    public function limit($rows, $offset = false);

    /**
     * Sets the order for the query
     *
     * @param $columnName_or_columnArray - has three possible types:
     *     $column
     *     array($columnone, $columntwo, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     * @return $this
     */
    public function orderBy($columnname_or_columnarray, $order = 'ASC', $useAliases = false);

    public function groupBy($columnname_or_columnarray);

    /**
     * Adds a JOIN clause
     *
     * @param $firsttable - tablename
     * @param $secondtable - tablename
     * @param array $on - must be in the format array('firsttablecolumn' => 'secondtablecolumn, ...)
     * @param string $type - inner|full|left|right
     * @return $this
     */
    public function join($firsttable, $secondtable, Array $on, $type = 'inner');

    /**
     * Adds a WHERE clause
     * Column names can use the format 'columnname operand' to use operands other than '=', e.g. 'id >'
     * Valid operands: >|<|>=|<=|like|in
     * If the tablename is not specified in the $where array parameter, $this->currentquery->table will be used
     * instead
     * Using the IN operand will make the param be treated as an array.
     * Setting the param to NULL will force the operand to IS.
     *
     * @param array $where - has three possible types:
     *     array($column => $param, ...)
     *     array($tableone => array($column => $param, ...), $tabletwo => array($column => $param, ...), ...)
     * @param string $innercondition - AND|OR - used between clauses in the WHERE statement
     * @param string $outercondition - AND|OR - used to append this WHERE statement to the query
     * @return $this
     */
    public function where(Array $where, $innercondition = 'AND', $outercondition = 'AND');
}
<?php
/**
 * Class MySqlSource
 *
 * MySQL compiler for optimized use of multiple databasees and/or dynamic queries (table and column
 * names not known ahead of time) using whitelists. Designed to cover 90% of use cases,
 * with the last 10% covered by $this->raw().
 */

namespace SypherLev\Blueprint\QueryBuilders\MySql;

use SypherLev\Blueprint\QueryBuilders\SourceInterface;
use SypherLev\Blueprint\QueryBuilders\QueryInterface;

class MySqlSource implements SourceInterface
{
    private $config;
    private $pdo;
    private $currentquery;
    private $recording = false;
    private $recording_output;
    private $in_transaction = false;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->currentquery = new MySqlQuery();
    }

    // TERMINATION METHODS
    // these methods are used to end the query chain, clear the query, and return a result

    public function one($sql = false, $binds = false)
    {
        if(!$sql) {
            $sql = $this->generateStatement();
        }
        if(!$binds) {
            $binds = $this->getAllBindings();
        }
        try {
            $statement = $this->pdo->prepare($sql);
            if (count($binds) > 0) {
                foreach ($binds as $idx => $val) {
                    $this->bindByType($statement, $idx, $val);
                }
            }
            $statement->execute();
            $return = $statement->fetch(\PDO::FETCH_OBJ);
            if($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $statement->errorInfo()
                );
            }
        } catch (\PDOException $e) {
            return false;
        }
        $this->reset();
        return $return;
    }

    public function many($sql = false, $binds = false)
    {
        if(!$sql) {
            $sql = $this->generateStatement();
        }
        if(!$binds) {
            $binds = $this->getAllBindings();
        }
        try {
            $statement = $this->pdo->prepare($sql);
            if (count($binds) > 0) {
                foreach ($binds as $idx => $val) {
                    $this->bindByType($statement, $idx, $val);
                }
            }
            $statement->execute();
            $return = $statement->fetchAll(\PDO::FETCH_OBJ);
            if($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $statement->errorInfo()
                );
            }
        } catch (\PDOException $e) {
            return false;
        }
        $this->reset();
        return $return;
    }

    public function count()
    {
        $this->currentquery->setCount(true);
        $return = $this->one();
        if($return) {
            return $return->count;
        }
        else {
            return false;
        }
    }

    public function execute($sql = false, $binds = false)
    {
        if(!$sql) {
            $sql = $this->generateStatement();
        }
        if(!$binds) {
            $binds = $this->getAllBindings();
        }
        try {
            $statement = $this->pdo->prepare($sql);
            if (count($binds) > 0) {
                foreach ($binds as $idx => $val) {
                    $this->bindByType($statement, $idx, $val);
                }
            }
            $return = $statement->execute();
            if($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $statement->errorInfo()
                );
            }
        } catch (\PDOException $e) {
            return false;
        }
        $this->reset();
        return $return;
    }

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
    public function raw($sql, $values, $fetch = '', $returntype = \PDO::FETCH_OBJ)
    {
        try {
            $statement = $this->pdo->prepare($sql);
            foreach ($values as $idx => $val) {
                $this->bindByType($statement, $idx, $val);
            }
            $return = $statement->execute();
            if ($fetch != '' && $return) {
                if ($fetch == 'fetch') {
                    $return = $statement->fetch($returntype);
                }
                if ($fetch == 'fetchAll') {
                    $return = $statement->fetchAll($returntype);
                }
            }
            if($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $values,
                    'error' => $statement->errorInfo()
                );
            }
            return $return;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function pattern() {
        $current = $this->currentquery;
        $this->reset();
        return $current;
    }

    // UTILITY FUNCTIONS

    // clears the currently compiled query
    public function reset()
    {
        $this->currentquery = new Query();
        return $this;
    }

    // returns the current database name
    public function getSchemaName()
    {
        return $this->config->database;
    }

    // get a list of columns from a table in the current database
    public function getTableColumns($tableName) {
        $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :tableName;";
        return $this->raw($sql, [':database' => $this->config->database, ':tableName' => $tableName], 'fetchAll');
    }

    /**
     * Gets a plain PHP object which contains the query and bindings. This is useful if you
     * want to execute the query elsewhere.
     *
     * @return \stdClass
     */
    public function retrieveQuery() {
        $query = new \stdClass();
        $query->compiledquery = $this->getCurrentSQL();
        $query->bindings = $this->getAllBindings();
        return $query;
    }

    /**
     * Sets a whitelist for the columns in the case where the query is executing with
     * user input as the column names
     *
     * @param array $whitelist
     * @return $this
     */
    public function setColumnWhitelist(Array $whitelist) {
        $this->currentquery->setColumnWhitelist($whitelist);
        return $this;
    }

    /**
     * Sets a whitelist for the tables in the case where the query is executing with
     * user input as the table names. Applies to the primary table and all joined tables
     *
     * @param array $whitelist
     * @return $this
     */
    public function setTableWhitelist(Array $whitelist) {
        $this->currentquery->setTableWhitelist($whitelist);
        return $this;
    }

    /**
     * Shorthand method to get the last saved ID from a table. Allows for ID names other than 'id'
     *
     * @param $table
     * @param string $primaryKeyname
     * @return mixed
     */
    public function lastIdFrom($table, $primaryKeyname = 'id')
    {
        $record = $this->select()->table($table)->columns([$primaryKeyname])->orderBy($primaryKeyname, 'DESC')->one();
        if(isset($record->{$primaryKeyname})) {
            return $record->{$primaryKeyname};
        }
        else {
            return false;
        }
    }

    // does whatever it says

    public function lastInsertId($name = null) {
        return $this->pdo->lastInsertId($name);
    }

    public function startTransaction() {
        $this->in_transaction = true;
        $this->pdo->beginTransaction();
    }

    public function commitTransaction() {
        if($this->in_transaction) {
            $return = $this->pdo->commit();
            if($return) {
                $this->in_transaction = false;
            }
            return $return;
        }
        else {
            return false;
        }
    }

    public function rollbackTransaction() {
        if($this->in_transaction) {
            $return = $this->pdo->rollBack();
            if($return) {
                $this->in_transaction = false;
            }
            return $return;
        }
        else {
            return false;
        }
    }

    // TESTING METHODS
    // these methods are used to check outputs and do query testing

    // start and stop recording queries, bindings, and statement errors
    public function startRecording() {
        $this->recording = true;
        $this->recording_output = [];
    }

    public function stopRecording() {
        $this->recording = false;
    }

    public function getRecordedOutput() {
        return $this->recording_output;
    }

    /**
     * Copies and returns the current query - useful for storing/rerunning failed queries
     *
     * @return QueryInterface $query
     */
    public function cloneQuery() {
        return clone $this->currentquery;
    }

    /**
     * Sets the current query to a cloned copy from $this->cloneQuery
     *
     * @param $query
     */
    public function setQuery(QueryInterface $query) {
        $this->currentquery = $query;
    }

    /**
     * Returns the current raw SQL statement based on the parameters in $this->currentquery
     * or throws an \Exception if required elements are missing
     *
     * @return mixed
     */
    public function getCurrentSQL() {
        return $this->generateStatement();
    }

    // COMPILER CHAIN METHODS
    // these methods return $this and allow further additions to the current query

    public function select()
    {
        $this->currentquery->setType('SELECT');
        return $this;
    }

    public function update()
    {
        $this->currentquery->setType('UPDATE');
        return $this;
    }

    public function insert()
    {
        $this->currentquery->setType('INSERT');
        return $this;
    }

    public function delete()
    {
        $this->currentquery->setType('DELETE');
        return $this;
    }

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
    public function columns($columnname_or_columnarray)
    {
        $this->currentquery->setColumns($columnname_or_columnarray);
        return $this;
    }

    /**
     * Set the primary table on the query
     *
     * @param string $tablename
     * @return $this
     */
    public function table($tablename)
    {
        $this->currentquery->setTable($tablename);
        return $this;
    }

    /**
     * Used to add records for INSERT statements
     * Use this in a loop to add a batch of records
     *
     * @param array $record - array('column' => $variable, ... )
     * @return $this
     */
    public function add(Array $record)
    {
        $this->currentquery->addInsertRecord($record);
        return $this;
    }

    /**
     * Used only with UPDATE
     *
     * @param array $set - array('column' => $variable, ... )
     * @return $this
     */
    public function set(Array $set)
    {
        $this->currentquery->setUpdates($set);
        return $this;
    }

    public function limit($rows, $offset = false)
    {
        if($offset !== false) {
            $this->currentquery->setLimit($rows, $offset);
        }
        else {
            $this->currentquery->setLimit($rows);
        }
        return $this;
    }

    /**
     * Sets the order for the query
     *
     * @param $columnName_or_columnArray - has three possible types:
     *     $column
     *     array($columnone, $columntwo, ...)
     *     array($tableone => array($columnone, $columntwo,  ...), $tabletwo => array(...), ...)
     * @return $this
     */
    public function orderBy($columnname_or_columnarray, $order = 'ASC', $useAliases = false)
    {
        if(!is_array($columnname_or_columnarray)) {
            $columnname_or_columnarray = [$columnname_or_columnarray];
        }
        $this->currentquery->setOrderBy($columnname_or_columnarray, $order, $useAliases = false);
        return $this;
    }

    public function groupBy($columnname_or_columnarray)
    {
        $this->currentquery->setGroupBy($columnname_or_columnarray);
        return $this;
    }

    // COMPILER NON-CHAIN METHODS
    // these are separated to allow for more complex queries

    /**
     * Adds a JOIN clause
     *
     * @param $firsttable - tablename
     * @param $secondtable - tablename
     * @param array $on - must be in the format array('firsttablecolumn' => 'secondtablecolumn, ...)
     * @param string $type - inner|full|left|right
     * @return $this
     */
    public function join($firsttable, $secondtable, Array $on, $type = 'inner')
    {
        $this->currentquery->setJoin($firsttable, $secondtable, $on, strtoupper($type));
        return $this;
    }

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
    public function where(Array $where, $innercondition = 'AND', $outercondition = 'AND')
    {
        $this->currentquery->setWhere($where, $innercondition, $outercondition);
        return $this;
    }

    // PRIVATE FUNCTIONS
    // LEAVE THIS STUFF ALONE

    // UTILITY METHODS
    private function generateNewPDO()
    {
        $dns = $this->config->engine . ':dbname=' . $this->config->database . ";host=" . $this->config->host;
        return new \PDO($dns, $this->config->user, $this->config->pass);
    }

    private function generateStatement()
    {
        return $this->currentquery->compile();
    }

    private function getAllBindings()
    {
        return $this->currentquery->getBindings();
    }

    private function bindByType(\PDOStatement &$statement, $param, $value) {
        if(is_int($value)) {
            $statement->bindValue($param, $value, \PDO::PARAM_INT);
        }
        else if(is_bool($value)) {
            $statement->bindValue($param, $value, \PDO::PARAM_BOOL);
        }
        else if(is_null($value)) {
            $statement->bindValue($param, $value, \PDO::PARAM_NULL);
        }
        else {
            $statement->bindValue($param, $value, \PDO::PARAM_STR);
        }
    }
}
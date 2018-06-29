<?php
/**
 * Class MySqlSource
 *
 * Executes MySql statements generated by a given Query object, or directly through the raw() function.
 */

namespace SypherLev\Blueprint\QueryBuilders\MySql;

use SypherLev\Blueprint\QueryBuilders\SourceInterface;
use SypherLev\Blueprint\QueryBuilders\QueryInterface;

class MySqlSource implements SourceInterface
{
    private $pdo;
    /* @var \SypherLev\Blueprint\QueryBuilders\MySql\MySqlQuery */
    private $currentquery;
    private $recording = false;
    private $recording_output;
    private $in_transaction = false;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function one($sql = false, $binds = false)
    {
        if (!$sql) {
            $sql = $this->generateStatement();
        }
        if (!$binds) {
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
            if ($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $statement->errorInfo()
                );
            }
        } catch (\Exception $e) {
            if ($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $e->getMessage()
                );
            }
            $return = false;
        }
        $this->reset();
        return $return;
    }

    public function many($sql = false, $binds = false)
    {
        if (!$sql) {
            $sql = $this->generateStatement();
        }
        if (!$binds) {
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
            if ($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $statement->errorInfo()
                );
            }
        } catch (\Exception $e) {
            if ($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $e->getMessage()
                );
            }
            $return = false;
        }
        $this->reset();
        return $return;
    }

    public function count()
    {
        $this->currentquery->setCount(true);
        $return = $this->one();
        if ($return) {
            return $return->count;
        } else {
            return false;
        }
    }

    public function execute($sql = false, $binds = false)
    {
        if (!$sql) {
            $sql = $this->generateStatement();
        }
        if (!$binds) {
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
            if ($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $statement->errorInfo()
                );
            }
        } catch (\Exception $e) {
            if ($this->recording) {
                $this->recording_output[] = array(
                    'sql' => $sql,
                    'binds' => $binds,
                    'error' => $e->getMessage()
                );
            }
            $return = false;
        }
        $this->reset();
        return $return;
    }

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
            if ($this->recording) {
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

    // UTILITY FUNCTIONS

    // set the current Query object
    public function setQuery(QueryInterface $query)
    {
        $this->currentquery = $query;
    }

    // clears the currently compiled Query object
    public function reset()
    {
        $this->currentquery = null;
        return $this;
    }

    // returns the current database name
    public function getDatabaseName()
    {
        return $this->pdo->query('select database()')->fetchColumn();
    }

    // get a list of columns from a table in the current database
    public function getTableColumns($tableName)
    {
        $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :database AND TABLE_NAME = :tableName;";
        $columns = $this->raw($sql, [':database' => $this->getDatabaseName(), ':tableName' => $tableName], 'fetchAll');
        $return_array = [];
        foreach ($columns as $c) {
            $return_array[] = $c->COLUMN_NAME;
        }
        return $return_array;
    }

    public function getPrimaryKey($tableName)
    {
        $database = $this->getDatabaseName();
        $sql = "SELECT k.column_name
FROM information_schema.table_constraints t
JOIN information_schema.key_column_usage k
USING(constraint_name,table_schema,table_name)
WHERE t.constraint_type='PRIMARY KEY'
  AND t.table_schema=:schemaName
  AND t.table_name=:tableName;";
        $result = $this->raw($sql, [':tableName' => $tableName, ':schemaName' => $database], 'fetch');
        if($result) {
            return $result->column_name;
        }
        return null;
    }

    // Wrappers for some useful PDO functions

    public function lastInsertId($name = null)
    {
        return $this->pdo->lastInsertId($name);
    }

    public function beginTransaction()
    {
        $this->in_transaction = true;
        $this->pdo->beginTransaction();
    }

    public function commit()
    {
        if ($this->in_transaction) {
            $return = $this->pdo->commit();
            if ($return) {
                $this->in_transaction = false;
            }
            return $return;
        } else {
            return false;
        }
    }

    public function rollBack()
    {
        if ($this->in_transaction) {
            $return = $this->pdo->rollBack();
            if ($return) {
                $this->in_transaction = false;
            }
            return $return;
        } else {
            return false;
        }
    }

    // TESTING METHODS
    // these methods are used to check outputs and do query testing

    // start and stop recording queries, bindings, and statement errors
    public function startRecording()
    {
        $this->recording = true;
        $this->recording_output = [];
    }

    public function stopRecording()
    {
        $this->recording = false;
    }

    public function getRecordedOutput()
    {
        return $this->recording_output;
    }

    public function generateNewQuery()
    {
        return new MySqlQuery();
    }

    // PRIVATE FUNCTIONS
    // LEAVE THIS STUFF ALONE

    // UTILITY METHODS

    private function generateStatement()
    {
        return $this->currentquery->compile();
    }

    private function getAllBindings()
    {
        return $this->currentquery->getBindings();
    }

    private function bindByType(\PDOStatement &$statement, $param, $value)
    {
        if (is_int($value)) {
            $statement->bindValue($param, $value, \PDO::PARAM_INT);
        } else if (is_bool($value)) {
            $statement->bindValue($param, $value, \PDO::PARAM_BOOL);
        } else if (is_null($value)) {
            $statement->bindValue($param, $value, \PDO::PARAM_NULL);
        } else {
            $statement->bindValue($param, $value, \PDO::PARAM_STR);
        }
    }
}
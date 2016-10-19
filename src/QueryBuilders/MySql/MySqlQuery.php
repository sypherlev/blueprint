<?php

namespace SypherLev\Blueprint\QueryBuilders\MySql;

use SypherLev\Blueprint\QueryBuilders\QueryInterface;

class MySqlQuery implements QueryInterface
{
    // init: use no whitelists
    private $columnwhitelist = [];
    private $tablewhitelist = [];

    // init: minimum required by the compiler
    private $type = false;
    private $table = false;

    // init: optional params; used if set

    // Patterns
    private $joins = [];
    private $columns = [];

    // Contexts
    private $records = [];
    private $updates = [];
    private $group = [];
    // init: this is not a count query
    private $count = false;

    // Filters
    private $wheres = [];
    private $order = [];
    private $direction = false;
    private $limit = false;

    private $bindings = [];

    private $allowedjoins = ['INNER', 'OUTER', 'LEFT', 'RIGHT'];
    private $allowedorders = ['ASC', 'DESC'];
    private $allowedtypes = ['SELECT', 'UPDATE', 'INSERT', 'DELETE'];

    // WHERE THE MAGIC HAPPENS

    public function compile() {
        // check for the bare minimum
        if($this->table === false || $this->type === false) {
            throw (new \Exception('Query compilation failure: missing table or type'));
        }
        $generatefunction = 'generate' . $this->type . 'Statement';
        return $this->{$generatefunction}();
    }

    private function generateSELECTStatement()
    {
        $query = $this->type . ' ';
        if (!empty($this->columns)) {
            $query .= $this->compileColumns();
        } else if ($this->count) {
            $query .= 'COUNT(*) AS count ';
        } else {
            $query .= '* ';
        }
        $query .= 'FROM `' . $this->table . '` ';
        if (!empty($this->joins)) {
            $query .= $this->compileJoins();
        }
        if (!empty($this->wheres)) {
            $query .= $this->compileWheres();
        }
        if (!empty($this->group)) {
            $query .= $this->compileGroup();
        }
        if (!empty($this->order)) {
            $query .= $this->compileOrder();
        }
        if ($this->limit !== false) {
            $query .= $this->compileLimit();
        }
        return $query;
    }

    private function generateDELETEStatement()
    {
        $query = $this->type . ' ';
        $query .= 'FROM `' . $this->table . '` ';
        if (!empty($this->wheres)) {
            $query .= $this->compileWheres();
        }
        return $query;
    }

    private function generateINSERTStatement()
    {
        if (empty($this->records)) {
            throw new \Exception('No records added for INSERT: statement cannot be executed.');
        }
        if(isset($this->columns)) {
            $tablevalid = false;
            foreach ($this->records as $record) {
                foreach ($record as $key => $set) {
                    $valid = false;
                    foreach ($this->columns as $column) {
                        if($column->table == $this->table) {
                            $tablevalid = true;
                        }
                        if($column->table == $this->table && $column->column == $key) {
                            $valid = true;
                        }
                    }
                    if(!$valid) {
                        throw new \Exception(' PHP :: Pattern mismatch: Column '.$key.' in table '.$this->table.' failed validation in INSERT');
                    }
                }
            }
            if(!$tablevalid) {
                throw new \Exception(' PHP :: Pattern mismatch: table ' . $this->table . ' failed validation in INSERT');
            }
        }
        $query = $this->type . ' INTO ';
        $query .= '`' . $this->table . '` ';
        if (!empty($this->columns)) {
            $query .= '(' . $this->compileColumns() . ') ';
        }
        $query .= 'VALUES ';
        $query .= $this->compileRecords();
        return $query;
    }

    private function generateUPDATEStatement()
    {
        if (empty($this->updates)) {
            throw new \Exception('No SET added for UPDATE: statement cannot be executed.');
        }
        if(isset($this->columns)) {
            $tablevalid = false;
            foreach ($this->updates as $update) {
                $valid = false;
                foreach ($this->columns as $column) {
                    if($column->table == $this->table) {
                        $tablevalid = true;
                    }
                    if($column->table == $this->table && $column->column == $update->column) {
                        $valid = true;
                    }
                }
                if(!$valid) {
                    throw new \Exception(' PHP :: Pattern mismatch: Column '.$update->column.' in table '.$this->table.' failed validation in UPDATE');
                }
            }
            if(!$tablevalid) {
                throw new \Exception(' PHP :: Pattern mismatch: table ' . $this->table . ' failed validation in UPDATE');
            }
        }
        $query = $this->type . ' ';
        $query .= '`' . $this->table . '` ';
        $query .= 'SET ' . $this->compileUpdates();
        if (!empty($this->wheres)) {
            $query .= $this->compileWheres();
        }
        return $query;
    }

    // QUERY CHUNK SETTERS

    public function setTable($tablename)
    {
        if (!empty($this->tablewhitelist)) {
            if (!in_array($tablename, $this->tablewhitelist)) {
                throw (new \Exception('Primary table name missing from whitelist'));
            }
        }
        $this->table = $tablename;
    }

    public function setType($type)
    {
        if (!in_array($type, $this->allowedtypes)) {
            throw (new \Exception('Disallowed query type: query must be one of SELECT|UPDATE|INSERT|DELETE'));
        }
        $this->type = $type;
    }

    public function setColumns(Array $columns)
    {
        if (empty($columns)) {
            throw (new \Exception('Columns list is empty'));
        }
        $table = false;
        if (!empty($this->table)) {
            $table = $this->table;
        }
        if (!$this->hasNumericKeys($columns)) {
            // then this is an aliased column list or a table list
            foreach ($columns as $key => $col) {
                if (is_array($col)) {
                    // then this is a table list, aliased or not
                    $tablename = $key;
                    foreach ($col as $alias => $innercol) {
                        if (!$this->hasNumericKeys($col)) {
                            // then this is an aliased table list
                            $this->newColumnEntry($innercol, $alias, $tablename);
                        } else {
                            // then this is a non-aliased table list
                            $this->newColumnEntry($innercol, false, $tablename);
                        }
                    }
                } else {
                    // then this is an aliased column list
                    $this->newColumnEntry($col, $key, $table);
                }
            }
        } else {
            // then this is a plain list of columns
            foreach ($columns as $column) {
                $this->newColumnEntry($column, false, $table);
            }
        }
    }

    public function setUpdates(Array $updates)
    {
        if (empty($updates)) {
            throw (new \Exception('Update array is empty'));
        }
        if ($this->hasNumericKeys($updates)) {
            throw (new \Exception('Invalid numeric key in update array; all keys must be strings'));
        }
        foreach ($updates as $column => $param) {
            $this->newUpdateEntry($column, $param);
        }
    }

    public function setCount($count = false) {
        $this->count = $count;
    }

    public function addInsertRecord(Array $record)
    {
        if ($this->hasNumericKeys($record)) {
            throw (new \Exception('Invalid numeric key in inserted record; all keys must be strings'));
        }
        if (empty($this->columns)) {
            $this->setColumns(array_keys($record));
        }
        else {
            $columns_to_check = array_keys($record);
            $validated_columns = [];
            foreach ($this->columns as $col) {
                if ($col->table == $this->table) {
                    $validated_columns[] = $col->column;
                }
            }
            foreach ($columns_to_check as $col) {
                if(!in_array($col, $validated_columns)) {
                    throw new \Exception(' PHP :: Pattern mismatch: Column '.$col.' in table '.$this->table.' failed validation in INSERT');
                }
            }
            $this->columns = [];
            $this->setColumns(array_keys($record));
        }
        $this->newInsertEntry($record);
    }

    public function setLimit($rows, $offset = 0) {
        $this->limit = new \stdClass();
        $this->limit->rows = $rows;
        $this->limit->offset = $offset;
    }

    public function setJoin($first, $second, Array $on, $type = 'INNER')
    {
        if (!in_array($type, $this->allowedjoins)) {
            throw (new \Exception('Disallowed JOIN type: joins must be one of INNER|OUTER|LEFT|RIGHT'));
        }
        if ($this->hasNumericKeys($on)) {
            throw (new \Exception('Bad join relations array: array must have string keys in the format column1 => column2'));
        }
        $this->newJoinEntry($first, $second, $on, $type);
    }

    public function setWhere(Array $where, $innercondition = 'AND', $outercondition = 'AND')
    {
        if (empty($where)) {
            throw (new \Exception('Where list is empty'));
        }
        $placeholder = 'sypherlev_blueprint_tablename_placeholder';
        foreach ($where as $key => $value) {
            if (is_array($value) && strpos($key, ' IN') === false) {
                // then this is an array of table => [column => param, ...]
                if ($this->hasNumericKeys($value) && strpos($key, ' IN') === false) {
                    throw (new \Exception('Bad where relations array: array must have string keys in the format column => param or table => [column => param]'));
                }
                $this->newWhereEntry($where, $innercondition, $outercondition);
                break;
            }
            else if(is_array($value) && strpos($key, ' IN') !== false) {
                // then this is an IN or NOT IN array
                $where = [$placeholder => $where];
                $this->newWhereEntry($where, $innercondition, $outercondition);
                break;
            }
            else {
                if ($this->hasNumericKeys($where)) {
                    throw (new \Exception('Bad where relations array: array must have string keys in the format column => param or table => [column => param]'));
                }
                $where = [$placeholder => $where];
                $this->newWhereEntry($where, $innercondition, $outercondition);
                break;
            }
        }
    }

    public function setOrderBy(Array $orderby, $direction = 'ASC', $aliases = false)
    {
        $table = false;
        if (!empty($this->table)) {
            $table = $this->table;
        }
        if (!in_array($direction, $this->allowedorders)) {
            throw (new \Exception('Disallowed ORDER BY type: order must be one of ASC|DESC'));
        }
        if (!$this->hasNumericKeys($orderby)) {
            // then this is an array of tables
            foreach ($orderby as $table => $cols) {
                if(is_array($cols)) {
                    foreach ($cols as $col) {
                        if (is_string($col)) {
                            $this->newOrderEntry($col, $table);
                        } else {
                            throw (new \Exception('Invalid non-string column name in ORDER BY clause'));
                        }
                    }
                }
                else {
                    if (is_string($cols)) {
                        $this->newOrderEntry($cols, $table);
                    } else {
                        throw (new \Exception('Invalid non-string column name in ORDER BY clause'));
                    }
                }
            }
        } else {
            // then this is a plain array of columns
            foreach ($orderby as $col) {
                if (is_string($col)) {
                    if ($aliases) {
                        $this->newOrderEntry($col);
                    } else {
                        $this->newOrderEntry($col, $table);
                    }
                } else {
                    throw (new \Exception('Invalid non-string column name in ORDER BY clause'));
                }
            }
        }
        $this->direction = $direction;
    }

    public function setGroupBy(Array $groupby)
    {
        $table = false;
        if (!empty($this->table)) {
            $table = $this->table;
        }
        if (!$this->hasNumericKeys($groupby)) {
            // then this is an array of tables
            foreach ($groupby as $table => $col) {
                if (is_string($col)) {
                    $this->newGroupEntry($table, $col);
                } else {
                    throw (new \Exception('Invalid non-string column name in GROUP BY clause'));
                }
            }
        } else {
            // then this is a plain array of columns
            foreach ($groupby as $col) {
                if (is_string($col)) {
                    $this->newGroupEntry($table, $col);
                } else {
                    throw (new \Exception('Invalid non-string column name in GROUP BY clause'));
                }
            }
        }
    }

    public function addToColumnWhitelist($column) {
        $this->columnwhitelist[] = $column;
    }

    public function addToTableWhitelist($table) {
        $this->tablewhitelist[] = $table;
    }

    public function getBindings() {
        $bindings = [];
        foreach ($this->bindings as $type => $bindinglist) {
            $bindings = array_merge($bindings, $bindinglist);
        }
        return $bindings;
    }

    public function getSection($sectionName) {
        if(property_exists($this, $sectionName)) {
            return $this->{$sectionName};
        }
        else {
            return false;
        }
    }

    // PRIVATE FUNCTIONS

    // COMPILATION STUFF

    private function compileColumns() {
        $columnstring = '';
        foreach ($this->columns as $columnentry) {
            if($columnentry->table == false) {
                $columnentry->table = $this->table;
            }
            if($columnentry->column == '*') {
                $columnstring .= '`'.$columnentry->table.'`.'.$columnentry->column;
            }
            else {
                $columnstring .= '`'.$columnentry->table.'`.`'.$columnentry->column.'`';
            }
            if($columnentry->alias !== false) {
                $columnstring .= ' AS `'.$columnentry->alias.'`';
            }
            $columnstring .= ', ';
        }
        return rtrim($columnstring, ', '). ' ';
    }

    private function compileJoins() {
        $compilestring = '';
        foreach ($this->joins as $joinentry) {
            $compilestring .= $joinentry->type. ' JOIN `'.$joinentry->secondtable. '` ON ';
            foreach ($joinentry->relations as $first => $second) {
                $compilestring .= '`'.$joinentry->firsttable.'`.`'.$first.'` = `'.$joinentry->secondtable.'`.`'.$second.'` AND ';
            }
            $compilestring = rtrim($compilestring, ' AND ');
            $compilestring .= ' ';
        }
        return $compilestring;
    }

    private function compileWheres() {
        $compilestring = 'WHERE ';
        foreach ($this->wheres as $whereentry) {
            foreach($whereentry->params as $table => $columns) {
                if($table === 'sypherlev_blueprint_tablename_placeholder') {
                    $table = $this->table;
                }
                $compilestring .= '(';
                foreach ($columns as $column => $placeholder) {
                    $operand = $this->checkOperand($column, $placeholder);
                    $compilestring .= '`'.$table.'`.`'.$this->stripOperands($column).'` '.$operand.' '.$placeholder.' '.$whereentry->inner.' ';
                }
                $compilestring = rtrim($compilestring, ' '.$whereentry->inner.' ');
                $compilestring .= ') '.$whereentry->outer.' ';
            }
        }
        $compilestring = rtrim($compilestring, 'AND ');
        return rtrim($compilestring, 'OR ').' ';
    }

    private function compileGroup() {
        $compilestring = 'GROUP BY ';
        foreach ($this->group as $groupentry) {
            $compilestring .= '`'.$groupentry->table.'`.`'.$groupentry->column.'`, ';
        }
        return rtrim($compilestring, ', ').' ';
    }

    private function compileOrder() {
        $compilestring = 'ORDER BY ';
        foreach ($this->order as $orderentry) {
            if($orderentry->table !== false) {
                $compilestring .= '`'.$orderentry->table.'`.';
            }
            $compilestring .= '`'.$orderentry->column.'`, ';
        }
        return rtrim($compilestring, ', ').' '.$this->direction.' ';
    }

    private function compileLimit() {
        return 'LIMIT '.(int)$this->limit->offset.', '.(int)$this->limit->rows.' ';
    }

    private function compileRecords() {
        $compilestring = '';
        foreach ($this->records as $record) {
            $compilestring .= '(';
            foreach ($record as $column => $placeholder) {
                $compilestring .= $placeholder.', ';
            }
            $compilestring = rtrim($compilestring, ', ').'), ';
        }
        return rtrim($compilestring, ', ').' ';
    }

    private function compileUpdates() {
        $compilestring = '';
        foreach ($this->updates as $updateentry) {
            $compilestring .= '`'.$updateentry->column.'` = '.$updateentry->param.', ';
        }
        return rtrim($compilestring, ', ').' ';
    }

    // OTHER STUFF

    private function hasNumericKeys(Array $array){
        foreach ($array as $key => $value) {
            if (!is_string($key)) {
                return true;
            }
        }
        return false;
    }

    private function newUpdateEntry($column, $param)
    {
        if (!empty($this->columnwhitelist)) {
            if (!in_array($column, $this->columnwhitelist)) {
                throw (new \Exception('Column in update list not found in white list'));
            }
        }
        $newupdate = new \stdClass();
        $newupdate->column = $column;
        $newupdate->param = $this->newBindEntry($param, ':up');
        $this->updates[] = $newupdate;
    }

    private function newColumnEntry($column, $alias, $table = false)
    {
        if (!empty($this->columnwhitelist)) {
            if (!in_array($column, $this->columnwhitelist)) {
                throw (new \Exception('Column in selection list not found in white list'));
            }
        }
        if (!empty($this->tablewhitelist)) {
            if ($table !== false && !in_array($table, $this->tablewhitelist)) {
                throw (new \Exception('Table name in selection list not found in white list'));
            }
        }
        $newcolumn = new \stdClass();
        $newcolumn->table = $table;
        $newcolumn->column = $column;
        $newcolumn->alias = $alias;
        $this->columns[] = $newcolumn;
    }

    private function newOrderEntry($column, $table = false)
    {
        if (!empty($this->columnwhitelist)) {
            if (!in_array($column, $this->columnwhitelist)) {
                throw (new \Exception('Column in ORDER BY not found in white list'));
            }
        }
        if (!empty($this->tablewhitelist)) {
            if ($table !== false && !in_array($table, $this->tablewhitelist)) {
                throw (new \Exception('Table name in ORDER BY not found in white list'));
            }
        }
        $neworder = new \stdClass();
        $neworder->table = $table;
        $neworder->column = $column;
        $this->order[] = $neworder;
    }

    private function newGroupEntry($table, $column)
    {
        if (!empty($this->columnwhitelist)) {
            if (!in_array($column, $this->columnwhitelist)) {
                throw (new \Exception('Column in GROUP BY not found in white list'));
            }
        }
        if (!empty($this->tablewhitelist)) {
            if ($table !== false && !in_array($table, $this->tablewhitelist)) {
                throw (new \Exception('Table name in GROUP BY not found in white list'));
            }
        }
        $newgroup = new \stdClass();
        $newgroup->table = $table;
        $newgroup->column = $column;
        $this->group[] = $newgroup;
    }

    private function newJoinEntry($firsttable, $secondtable, Array $relations, $type)
    {
        foreach ($relations as $column1 => $column2) {
            if (!empty($this->columnwhitelist)) {
                if (!in_array($column1, $this->columnwhitelist) || !in_array($column2, $this->columnwhitelist)) {
                    throw (new \Exception('Column in JOIN not found in white list'));
                }
            }
        }
        if (!empty($this->tablewhitelist)) {
            if (!in_array($firsttable, $this->tablewhitelist) || !in_array($secondtable, $this->tablewhitelist)) {
                throw (new \Exception('Table name in ORDER BY not found in white list'));
            }
        }
        $newjoin = new \stdClass();
        $newjoin->firsttable = $firsttable;
        $newjoin->secondtable = $secondtable;
        $newjoin->relations = $relations;
        $newjoin->type = $type;
        $this->joins[] = $newjoin;
    }

    private function newWhereEntry($paramArray, $inner, $outer)
    {
        if (!empty($this->columnwhitelist)) {
            foreach ($paramArray as $table => $columns) {
                foreach ($columns as $column => $param) {
                    $column = $this->stripOperands($column);
                    if (!in_array($column, $this->columnwhitelist)) {
                        throw (new \Exception('Column in WHERE not found in white list'));
                    }
                }
            }
        }
        if (!empty($this->tablewhitelist)) {
            foreach ($paramArray as $table => $columns) {
                if ($table !== false && !in_array($table, $this->tablewhitelist)) {
                    throw (new \Exception('Table name in WHERE not found in white list'));
                }
            }
        }
        foreach ($paramArray as $table => $columns) {
            foreach ($columns as $column => $param) {
                if(strpos($column, ' IN') !== false && is_array($param)) {
                    $paramstring = '(';
                    foreach ($param as $in) {
                        $paramstring .= $this->newBindEntry($in).', ';
                    }
                    $paramstring = rtrim($paramstring, ', ').')';
                    $paramArray[$table][$column] = $paramstring;
                }
                else if($param !== null) {
                    $paramArray[$table][$column] = $this->newBindEntry($param);
                }
                else {
                    $paramArray[$table][$column] = 'NULL';
                }
            }
        }
        $newwhere = new \stdClass();
        $newwhere->params = $paramArray;
        $newwhere->inner = $inner;
        $newwhere->outer = $outer;
        $this->wheres[] = $newwhere;
    }

    private function newInsertEntry(Array $record)
    {
        if (!empty($this->columnwhitelist)) {
            foreach ($record as $column => $param) {
                if (!in_array($column, $this->columnwhitelist)) {
                    throw (new \Exception('Column in INSERT array not found in white list'));
                }
            }
        }
        foreach ($record as $column => $param) {
            $record[$column] = $this->newBindEntry($param, ':ins');
        }
        $this->records[] = $record;
    }

    private function newBindEntry($param, $type = ':wh') {
        if(!isset($this->bindings[$type])) {
            $this->bindings[$type] = [];
        }
        $count = count($this->bindings[$type]);
        $this->bindings[$type][$type.$count] = $param;
        return $type.$count;
    }

    private function checkOperand($variable, $param)
    {
        if($param == 'NULL' && strpos($variable, '!=') !== false) {
            return 'IS NOT';
        }
        if (strpos($variable, '!==') !== false) {
            return '!==';
        }
        if (strpos($variable, '!=') !== false) {
            return '!=';
        }
        if (strpos($variable, '>=') !== false) {
            return '>=';
        }
        if (strpos($variable, '<=') !== false) {
            return '<=';
        }
        if (strpos($variable, '>') !== false) {
            return '>';
        }
        if (strpos($variable, '<') !== false) {
            return '<';
        }
        if (strpos(strtolower($variable), ' not like') !== false) {
            return 'NOT LIKE';
        }
        if (strpos(strtolower($variable), ' like') !== false) {
            return 'LIKE';
        }
        if (strpos(strtolower($variable), ' not in') !== false) {
            return 'NOT IN';
        }
        if (strpos(strtolower($variable), ' in') !== false) {
            return 'IN';
        }
        if($param === 'NULL') {
            return 'IS';
        }
        return '=';
    }

    private function stripOperands($variable)
    {
        $variable = strtolower($variable);
        $variable = preg_replace('/ not like$/', '', $variable);
        $variable = preg_replace('/ like$/', '', $variable);
        $variable = preg_replace('/ not in$/', '', $variable);
        $variable = preg_replace('/ in$/', '', $variable);
        $variable = rtrim($variable, '>=');
        $variable = rtrim($variable, '!==');
        $variable = rtrim($variable, '!=');
        $variable = rtrim($variable, '<=');
        $variable = rtrim($variable, '>');
        $variable = rtrim($variable, '<');
        return rtrim($variable, ' ');
    }
}
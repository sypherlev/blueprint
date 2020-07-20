<?php

namespace SypherLev\Blueprint\QueryBuilders\Postgres;

use SypherLev\Blueprint\Error\BlueprintException;
use SypherLev\Blueprint\QueryBuilders\QueryInterface;

class PostgresQuery implements QueryInterface
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
    private $aggregates = [];
    // init: this is not a count query
    private $count = false;

    // Filters
    private $wheres = [];
    private $order = [];
    private $direction = false;
    private $limit;

    private $bindings = [];

    private $allowedjoins = ['INNER', 'OUTER', 'LEFT', 'RIGHT'];
    private $allowedorders = ['ASC', 'DESC'];
    private $allowedaggregates = ['SUM', 'COUNT', 'AVG', 'MAX', 'MIN'];
    private $allowedtypes = ['SELECT', 'UPDATE', 'INSERT', 'DELETE'];

    // WHERE THE MAGIC HAPPENS

    public function compile(): string
    {
        // check for the bare minimum
        if ($this->table === false || $this->type === false) {
            throw (new BlueprintException('Query compilation failure: missing table or type'));
        }
        switch ($this->type) {
            case 'UPDATE':
                return $this->generateUPDATEStatement();
            case 'INSERT':
                return $this->generateINSERTStatement();
            case 'DELETE':
                return $this->generateDELETEStatement();
            default:
                return $this->generateSELECTStatement();
        }
    }

    private function generateSELECTStatement(): string
    {
        $query = $this->type . ' ';
        if (!empty($this->columns)) {
            $query .= $this->compileColumns();
            if (!empty($this->aggregates)) {
                $query = rtrim($query, ' ');
                $query .= ', ' . $this->compileAggregates();
            }
        } else if (!empty($this->aggregates)) {
            $query .= $this->compileAggregates();
        } else if ($this->count) {
            $query .= 'COUNT(*) AS "count" ';
        } else {
            $query .= '* ';
        }
        $query .= 'FROM "' . $this->table . '" ';
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
        if (!is_null($this->limit)) {
            $query .= $this->compileLimit();
        }
        return $query;
    }

    private function generateDELETEStatement(): string
    {
        $query = $this->type . ' ';
        $query .= 'FROM "' . $this->table . '" ';
        if (!empty($this->wheres)) {
            $query .= $this->compileWheres();
        }
        return $query;
    }

    private function generateINSERTStatement(): string
    {
        if (empty($this->records)) {
            throw new BlueprintException('No records added for INSERT: statement cannot be executed.');
        }
        if (!empty($this->columns)) {
            foreach ($this->records as $record) {
                foreach ($record as $key => $set) {
                    $valid = false;
                    foreach ($this->columns as $column) {
                        if ($column->table == $this->table && $column->column == $key) {
                            $valid = true;
                        }
                    }
                    if (!$valid) {
                        throw new BlueprintException(' PHP :: Pattern mismatch: Column ' . $key . ' in table ' . $this->table . ' failed validation in INSERT');
                    }
                }
            }
        }
        $query = $this->type . ' INTO ';
        $query .= '"' . $this->table . '" ';
        if (!empty($this->columns)) {
            $columns = $this->compileColumns();
            $columns = str_replace('"' . $this->table . '".', '', $columns);
            $query .= '(' . $columns . ') ';
        }
        $query .= 'VALUES ';
        $query .= $this->compileRecords();
        return $query;
    }

    private function generateUPDATEStatement(): string
    {
        if (empty($this->updates)) {
            throw new BlueprintException('No SET added for UPDATE: statement cannot be executed.');
        }
        if (!empty($this->columns)) {
            foreach ($this->updates as $update) {
                $valid = false;
                foreach ($this->columns as $column) {
                    if ($column->table == $this->table && $column->column == $update->column) {
                        $valid = true;
                    }
                }
                if (!$valid) {
                    throw new BlueprintException(' PHP :: Pattern mismatch: Column ' . $update->column . ' in table ' . $this->table . ' failed validation in UPDATE');
                }
            }
        }
        $query = $this->type . ' ';
        $query .= '"' . $this->table . '" ';
        $query .= 'SET ' . $this->compileUpdates();
        if (!empty($this->wheres)) {
            $query .= $this->compileWheres();
        }
        return $query;
    }

    // QUERY CHUNK SETTERS

    public function setTable(string $tablename)
    {
        if (!$this->validateTableName($tablename)) {
            throw (new BlueprintException('Primary table name missing from whitelist'));
        }
        $this->table = $tablename;
    }

    public function setType(string $type)
    {
        $type = strtoupper($type);
        if (!in_array($type, $this->allowedtypes)) {
            throw (new BlueprintException('Disallowed query type: query must be one of ' . implode('|', $this->allowedtypes)));
        }
        $this->type = $type;
    }

    public function setColumns(array $columns)
    {
        if (empty($columns)) {
            throw (new BlueprintException('Columns list is empty'));
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
                            $this->newColumnEntry($innercol, "", $tablename);
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
                $this->newColumnEntry($column, "", $table);
            }
        }
    }

    public function setUpdates(array $updates)
    {
        if (empty($updates)) {
            throw (new BlueprintException('Update array is empty'));
        }
        if ($this->hasNumericKeys($updates)) {
            throw (new BlueprintException('Invalid numeric key in update array; all keys must be strings'));
        }
        foreach ($updates as $column => $param) {
            $this->newUpdateEntry($column, $param);
        }
    }

    public function setCount(bool $count = false)
    {
        $this->count = $count;
    }

    public function addInsertRecord(array $record)
    {
        if ($this->hasNumericKeys($record)) {
            throw (new BlueprintException('Invalid numeric key in inserted record; all keys must be strings'));
        }
        if (empty($this->columns)) {
            $this->setColumns(array_keys($record));
        } else {
            $columns_to_check = array_keys($record);
            $validated_columns = [];
            foreach ($this->columns as $col) {
                if ($col->table == $this->table) {
                    $validated_columns[] = $col->column;
                }
            }
            foreach ($columns_to_check as $col) {
                if (!in_array($col, $validated_columns)) {
                    throw new BlueprintException(' PHP :: Pattern mismatch: Column ' . $col . ' in table ' . $this->table . ' failed validation in INSERT');
                }
            }
            $this->columns = [];
            $this->setColumns(array_keys($record));
        }
        $this->newInsertEntry($record);
    }

    public function setLimit(int $rows, int $offset = 0)
    {
        $this->limit = new \stdClass();
        $this->limit->rows = $rows;
        $this->limit->offset = $offset;
    }

    public function setJoin(string $first, string $second, array $on, string $type = 'INNER')
    {
        $type = strtoupper($type);
        if (!in_array($type, $this->allowedjoins)) {
            throw (new BlueprintException('Disallowed JOIN type: joins must be one of ' . implode('|', $this->allowedjoins)));
        }
        if ($this->hasNumericKeys($on)) {
            throw (new BlueprintException('Bad join relations array: array must have string keys in the format column1 => column2'));
        }
        $this->newJoinEntry($first, $second, $on, $type);
    }

    public function setWhere(array $where, string $innercondition = 'AND', string $outercondition = 'AND')
    {
        if (empty($where)) {
            throw (new BlueprintException('Where list is empty'));
        }
        $placeholder = 'sypherlev_blueprint_tablename_placeholder';
        foreach ($where as $key => $value) {
            if (is_array($value) && strpos(strtoupper($key), ' IN') === false) {
                // then this is an array of table => [column => param, ...]
                if ($this->hasNumericKeys($value) && strpos($key, ' IN') === false) {
                    throw (new BlueprintException('Bad where relations array: array must have string keys in the format column => param or table => [column => param]'));
                }
                $this->newWhereEntry($where, $innercondition, $outercondition);
                break;
            } else if (is_array($value) && strpos(strtoupper($key), ' IN') !== false) {
                // then this is an IN or NOT IN array
                $where = [$placeholder => $where];
                $this->newWhereEntry($where, $innercondition, $outercondition);
                break;
            } else {
                if ($this->hasNumericKeys($where)) {
                    throw (new BlueprintException('Bad where relations array: array must have string keys in the format column => param or table => [column => param]'));
                }
                $where = [$placeholder => $where];
                $this->newWhereEntry($where, $innercondition, $outercondition);
                break;
            }
        }
    }

    public function setOrderBy(array $orderby, string $direction = 'ASC', bool $aliases = false)
    {
        $table = false;
        if (!empty($this->table)) {
            $table = $this->table;
        }
        $direction = strtoupper($direction);
        if (!in_array($direction, $this->allowedorders)) {
            throw (new BlueprintException('Disallowed ORDER BY type: order must be one of ' . implode('|', $this->allowedorders)));
        }
        if (!$this->hasNumericKeys($orderby)) {
            // then this is an array of tables
            foreach ($orderby as $table => $cols) {
                if (is_array($cols)) {
                    foreach ($cols as $col) {
                        if (is_string($col)) {
                            $this->newOrderEntry($col, $table);
                        } else {
                            throw (new BlueprintException('Invalid non-string column name in ORDER BY clause'));
                        }
                    }
                } else {
                    if (is_string($cols)) {
                        $this->newOrderEntry($cols, $table);
                    } else {
                        throw (new BlueprintException('Invalid non-string column name in ORDER BY clause'));
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
                    throw (new BlueprintException('Invalid non-string column name in ORDER BY clause'));
                }
            }
        }
        $this->direction = $direction;
    }

    public function setAggregate(string $function, array $columns)
    {
        $table = false;
        if (!empty($this->table)) {
            $table = $this->table;
        }
        $function = strtoupper($function);
        if (!in_array($function, $this->allowedaggregates)) {
            throw (new BlueprintException('Disallowed aggregate function: aggregate must be one of ' . implode('|', $this->allowedaggregates)));
        }
        if ($this->hasNumericKeys($columns)) {
            // then this is an array in the form [columns1, column2, ...]
            foreach ($columns as $columnName) {
                if(!is_string($columnName)) {
                    throw (new BlueprintException('Disallowed column name '.$columnName.' in aggregate assignment: Column names may only be strings'));
                }
                $this->newAggregateEntry($function, $columnName, $table, "");
            }
        } else {
            // then this is an array in the form [tableName => [column1, column2, ...]] OR [alias => columnname]
            foreach ($columns as $tableName => $inner_columns) {
                if (is_array($inner_columns)) {
                    // [tablename => [column1, column2, ...]]
                    foreach ($inner_columns as $idx => $col) {
                        if(!is_string($col)) {
                            throw (new BlueprintException('Disallowed column name '.$col.' in aggregate assignment: Column names may only be strings'));
                        }
                        if (is_string($idx)) { // then aliases are in use
                            $this->newAggregateEntry($function, $col, $tableName, $idx);
                        } else {
                            $this->newAggregateEntry($function, $col, $tableName, "");
                        }
                    }
                } else {
                    // [alias => columnname]
                    if(!is_string($inner_columns)) {
                        throw (new BlueprintException('Disallowed column name '.$inner_columns.' in aggregate assignment: Column names may only be strings'));
                    }
                    $this->newAggregateEntry($function, $inner_columns, $table, $tableName);
                }
            }
        }
    }

    public function setGroupBy(array $groupby)
    {
        $table = false;
        if (!empty($this->table)) {
            $table = $this->table;
        }
        if (!$this->hasNumericKeys($groupby)) {
            // then this is an array of tables => columns
            foreach ($groupby as $table => $cols) {
                if (is_string($cols)) {
                    $this->newGroupEntry($cols, $table);
                } else if (is_array($cols)) {
                    foreach ($cols as $col) {
                        $this->newGroupEntry($col, $table);
                    }
                } else {
                    throw (new BlueprintException('Invalid non-string column name in GROUP BY clause'));
                }
            }
        } else {
            // then this is a plain array of columns
            foreach ($groupby as $col) {
                if (is_string($col)) {
                    $this->newGroupEntry($col, $table);
                } else {
                    throw (new BlueprintException('Invalid non-string column name in GROUP BY clause'));
                }
            }
        }
    }

    public function addToColumnWhitelist(array $columns)
    {
        foreach ($columns as $col) {
            $this->columnwhitelist[] = $col;
        }
    }

    public function addToTableWhitelist(array $tables)
    {
        foreach ($tables as $tab) {
            $this->tablewhitelist[] = $tab;
        }
    }

    public function getBindings() : array
    {
        $bindings = [];
        foreach ($this->bindings as $type => $bindinglist) {
            foreach ($bindinglist as $idx => $bind) {
                $bindings[$idx] = $bind;
            }
        }
        return $bindings;
    }

    public function getSection(string $sectionName) : array
    {
        if (property_exists($this, $sectionName)) {
            return $this->{$sectionName};
        } else {
            return [];
        }
    }

    // PRIVATE FUNCTIONS

    // COMPILATION FUNCTIONS

    private function compileColumns() : string
    {
        $columnstring = '';
        foreach ($this->columns as $columnentry) {
            if ($columnentry->table == false) {
                $columnentry->table = $this->table;
            }
            if ($columnentry->column == '*') {
                $columnstring .= '"' . $columnentry->table . '".' . $columnentry->column;
            } else {
                $columnstring .= '"' . $columnentry->table . '"."' . $columnentry->column . '"';
            }
            if ($columnentry->alias !== "") {
                $columnstring .= ' AS "' . $columnentry->alias . '"';
            }
            $columnstring .= ', ';
        }
        return rtrim($columnstring, ', ') . ' ';
    }

    private function compileAggregates() : string
    {
        $aggregatestring = '';
        foreach ($this->aggregates as $aggentry) {
            if ($aggentry->table == false) {
                $aggentry->table = $this->table;
            }
            if ($aggentry->alias === "") {
                $aggentry->alias = $aggentry->column;
            }
            $aggregatestring .= $aggentry->function . '("' . $aggentry->table . '"."' . $aggentry->column . '")';
            if ($aggentry->alias !== "") {
                $aggregatestring .= ' AS "' . $aggentry->alias . '"';
            }
            $aggregatestring .= ', ';
        }
        return rtrim($aggregatestring, ', ') . ' ';
    }

    private function compileJoins() : string
    {
        $compilestring = '';
        foreach ($this->joins as $joinentry) {
            $compilestring .= $joinentry->type . ' JOIN "' . $joinentry->secondtable . '" ON ';
            foreach ($joinentry->relations as $first => $second) {
                $compilestring .= '"' . $joinentry->firsttable . '"."' . $first . '" = "' . $joinentry->secondtable . '"."' . $second . '" AND ';
            }
            $compilestring = rtrim($compilestring, ' AND ');
            $compilestring .= ' ';
        }
        return $compilestring;
    }

    private function compileWheres() : string
    {
        $compilestring = 'WHERE ';
        foreach ($this->wheres as $whereentry) {
            foreach ($whereentry->params as $table => $columns) {
                if ($table === 'sypherlev_blueprint_tablename_placeholder' || $table === false) {
                    $table = $this->table;
                }
                $compilestring .= '(';
                foreach ($columns as $column => $placeholder) {
                    $operand = $this->checkOperand($column, $placeholder);
                    $compilestring .= '"' . $table . '"."' . $this->stripOperands($column) . '" ' . $operand . ' ' . $placeholder . ' ' . $whereentry->inner . ' ';
                }
                $compilestring = rtrim($compilestring, ' ' . $whereentry->inner . ' ');
                $compilestring .= ') ' . $whereentry->outer . ' ';
            }
        }
        $compilestring = rtrim($compilestring, 'AND ');
        return rtrim($compilestring, 'OR ') . ' ';
    }

    private function compileGroup() : string
    {
        $compilestring = 'GROUP BY ';
        foreach ($this->group as $groupentry) {
            $compilestring .= '"' . $groupentry->table . '"."' . $groupentry->column . '", ';
        }
        return rtrim($compilestring, ', ') . ' ';
    }

    private function compileOrder() : string
    {
        $compilestring = 'ORDER BY ';
        foreach ($this->order as $orderentry) {
            if ($orderentry->table !== false) {
                $compilestring .= '"' . $orderentry->table . '".';
            }
            $compilestring .= '"' . $orderentry->column . '", ';
        }
        return rtrim($compilestring, ', ') . ' ' . $this->direction . ' ';
    }

    private function compileLimit() : string
    {
        return 'OFFSET ' . (int)$this->limit->offset . ' LIMIT ' . (int)$this->limit->rows . ' ';
    }

    private function compileRecords() : string
    {
        $compilestring = '';
        foreach ($this->records as $record) {
            $compilestring .= '(';
            foreach ($record as $column => $placeholder) {
                $compilestring .= $placeholder . ', ';
            }
            $compilestring = rtrim($compilestring, ', ') . '), ';
        }
        return rtrim($compilestring, ', ') . ' ';
    }

    private function compileUpdates() : string
    {
        $compilestring = '';
        foreach ($this->updates as $updateentry) {
            $compilestring .= '"' . $updateentry->column . '" = ' . $updateentry->param . ', ';
        }
        return rtrim($compilestring, ', ') . ' ';
    }

    // PARSING AND VALIDATION FUNCTIONS

    private function newUpdateEntry(string $column, $param)
    {
        if (!empty($this->columnwhitelist)) {
            if (!in_array($column, $this->columnwhitelist)) {
                throw (new BlueprintException('Column in update list not found in white list'));
            }
        }
        $newupdate = new \stdClass();
        $newupdate->column = $column;
        $newupdate->param = $this->newBindEntry($param, ':up');
        $this->updates[] = $newupdate;
    }

    private function newColumnEntry(string $column, string $alias, $table = "")
    {
        if (!$this->validateColumnName($column)) {
            throw (new BlueprintException('Column '.$column.' in selection list not found in white list'));
        }
        if ($table !== "" && !$this->validateTableName($table)) {
            throw (new BlueprintException('Table name '.$table.' in selection list not found in white list'));
        }
        $newcolumn = new \stdClass();
        $newcolumn->table = $table;
        $newcolumn->column = $column;
        $newcolumn->alias = $alias;
        $this->columns[] = $newcolumn;
    }

    private function newOrderEntry(string $column, string $table = "")
    {
        if (!$this->validateColumnName($column)) {
            throw (new BlueprintException('Column '.$column.' in ORDER BY not found in white list'));
        }
        if ($table !== "" && !$this->validateTableName($table)) {
            throw (new BlueprintException('Table name '.$table.' in ORDER BY not found in white list'));
        }
        $neworder = new \stdClass();
        $neworder->table = $table;
        $neworder->column = $column;
        $this->order[] = $neworder;
    }

    private function newAggregateEntry(string $function, string $column, string $table = "", string $alias = '')
    {
        if (!$this->validateColumnName($column)) {
            throw (new BlueprintException("Column in $function(\"$table\".\"$column\") not found in white list"));
        }
        if ($table !== "" && !$this->validateTableName($table)) {
            throw (new BlueprintException("Table name in $function(\"$table\".\"$column\") not found in white list"));
        }
        $newagg = new \stdClass();
        $newagg->table = $table;
        $newagg->column = $column;
        $newagg->function = $function;
        $newagg->alias = $alias;
        $this->aggregates[] = $newagg;
    }

    private function newGroupEntry(string $column, string $table = "")
    {
        if (!$this->validateColumnName($column)) {
            throw (new BlueprintException('Column in GROUP BY not found in white list'));
        }
        if ($table !== "" && !$this->validateTableName($table)) {
            throw (new BlueprintException('Table name in GROUP BY not found in white list'));
        }
        $newgroup = new \stdClass();
        $newgroup->table = $table;
        $newgroup->column = $column;
        $this->group[] = $newgroup;
    }

    private function newJoinEntry(string $firsttable, string $secondtable, array $relations, string $type)
    {
        foreach ($relations as $column1 => $column2) {
            if (!$this->validateColumnName($column1)) {
                throw (new BlueprintException('Column '.$column1.' in JOIN not found in white list'));
            }
            if (!$this->validateColumnName($column2)) {
                throw (new BlueprintException('Column '.$column2.' in JOIN not found in white list'));
            }
        }
        if (!$this->validateTableName($firsttable)) {
            throw (new BlueprintException('Table name '.$firsttable.' in JOIN not found in white list'));
        }
        if (!$this->validateTableName($secondtable)) {
            throw (new BlueprintException('Table name '.$secondtable.' in JOIN not found in white list'));
        }
        $newjoin = new \stdClass();
        $newjoin->firsttable = $firsttable;
        $newjoin->secondtable = $secondtable;
        $newjoin->relations = $relations;
        $newjoin->type = $type;
        $this->joins[] = $newjoin;
    }

    private function newWhereEntry(array $paramArray, string $inner, string $outer)
    {
        foreach ($paramArray as $table => $columns) {
            if (!$this->validateTableName($table)) {
                throw (new BlueprintException('Table name '.$table.' in WHERE not found in white list'));
            }
            foreach ($columns as $column => $param) {
                if (!$this->validateColumnName($column)) {
                    throw (new BlueprintException('Column '.$column.' in WHERE not found in white list'));
                }
                if (strpos(strtoupper($column), ' IN') !== false && is_array($param)) {
                    $paramstring = '(';
                    foreach ($param as $in) {
                        $paramstring .= $this->newBindEntry($in) . ', ';
                    }
                    $paramstring = rtrim($paramstring, ', ') . ')';
                    $paramArray[$table][$column] = $paramstring;
                } else if ($param !== null) {
                    $paramArray[$table][$column] = $this->newBindEntry($param);
                } else {
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

    private function newInsertEntry(array $record)
    {
        foreach ($record as $column => $param) {
            // insert record column validation is handled by $this->setColumns()
            // no need to add it here
            $record[$column] = $this->newBindEntry($param, ':ins');
        }
        $this->records[] = $record;
    }

    private function newBindEntry($param, string $type = ':wh')
    {
        if (!isset($this->bindings[$type])) {
            $this->bindings[$type] = [];
        }
        $count = count($this->bindings[$type]);
        $this->bindings[$type][$type . $count] = $param;
        return $type . $count;
    }

    // ADDITONAL FUNCTIONS

    private function checkOperand(string $variable, $param) : string
    {
        if ($param == 'NULL' && strpos($variable, '!=') !== false) {
            return 'IS NOT';
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
        if ($param === 'NULL') {
            return 'IS';
        }
        return '=';
    }

    private function stripOperands(string $variable) : string
    {
        $variable = strtolower($variable);
        $variable = trim($variable);
        $variable = preg_replace('/ not like$/', '', $variable);
        $variable = preg_replace('/ like$/', '', $variable);
        $variable = preg_replace('/ not in$/', '', $variable);
        $variable = preg_replace('/ in$/', '', $variable);
        $variable = rtrim($variable, '>=');
        $variable = rtrim($variable, '!=');
        $variable = rtrim($variable, '<=');
        $variable = rtrim($variable, '>');
        $variable = rtrim($variable, '<');
        return rtrim($variable, ' ');
    }

    private function hasNumericKeys(array $array) : bool
    {
        foreach ($array as $key => $value) {
            if (!is_string($key)) {
                return true;
            }
        }
        return false;
    }

    private function validateTableName(string $table) : bool
    {
        if($table === 'sypherlev_blueprint_tablename_placeholder') {
            // the placeholder table name is always valid
            return true;
        }
        if (!empty($this->tablewhitelist) && !in_array($table, $this->tablewhitelist)) {
            return false;
        }
        return true;
    }

    private function validateColumnName(string $column) : bool
    {
        $column = $this->stripOperands($column);
        if (!empty($this->columnwhitelist) && !in_array($column, $this->columnwhitelist)) {
            return false;
        }
        return true;
    }
}